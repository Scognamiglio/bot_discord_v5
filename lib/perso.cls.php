<?php


class perso
{
    protected $idPerso;
    protected $exist = false;
    protected $allVoie = [];
    protected $champRecup = ['niveau','xp','VP','VS','nameVP','nameVS','pta','ptaTotal'];
    protected $champKey = ['VP','VS'];
    protected $architect = [
        'fiche' => ['niveau','xp','VP','VS','pta','ptaTotal']
    ];
    protected $niveau;
    protected $xp;
    public function __construct($idPerso)
    {
        $this->idPerso = $idPerso;
        $this->getData();
    }


    // Accesseur
    public function set($label,$value){$this->$label = $value;}
    public function get($label){if(isset($this->$label)){return $this->$label;}return null;}

    // Accesseur avec impact bdd
    public function update($label,$value){
        $this->set($label,$value);
        if(in_array($label,$this->architect['fiche'])){
            sql::query("update perso set $label=$value where idperso='".$this->idPerso."'");
        }
    }

    // Récupére data importante.
    public function getData(){
        $perso = sql::fetch("SELECT niveau,xp,VP,VS,vp.value AS nameVP,vs.value AS nameVS,pta,ptaTotal
            FROM perso p
            INNER JOIN ficheData vp ON p.idPerso=vp.idPerso AND vp.label='vPrimaire'
            INNER JOIN ficheData vs ON p.idPerso=vs.idPerso AND vs.label='vSecondaire'
            WHERE p.idperso='".$this->idPerso."'");
        if(empty($perso)){
            $this->exist = false;
            return;
        }
        $this->exist = true;
        foreach ($this->champRecup as $champ){
            $this->set($champ,$perso[$champ]);
        }
    }

    public function levelUPChar($newLevel){
        if(!$this->exist){return false;}
        $this->update('niveau',$newLevel);
        return true;
    }

    // Formule d'xp temporaire.
    public function xpForLevelUp(){
        if($this->get('niveau') >= 5){
            return false;
        }
        return 50 * pow(2,$this->get('niveau'));
    }


    public function addXp($xp){

        if(!$this->exist){return false;}
        if($this->get('niveau') == 5){
            return 'max';
        }

        // Formule d'xp temporaire.
        $xpLevel = $this->xpForLevelUp();
        $newXp = $this->get('xp') + $xp;
        if($xpLevel <= $newXp){
            $this->levelUPChar($this->get('niveau')+1);
            $newXp -=$xpLevel;
        }
        $this->update('xp',$newXp);
        return $newXp;
    }

    public function addXpVoie($xp,$voie){
        $r = $this->parseAllData();
        $key = null;
        foreach ($this->champKey as $champ){
            if(isset($r[$champ][$voie])){
                $key = $champ;
                break;
            }
        }
        $oldXp = $this->get($key);
        $newXp = round($oldXp + $xp * ($key == "VP" ? 1.5 : 1));
        $this->update($key,$newXp);
        $qryGetNewSkill = "SELECT name FROM skill WHERE SUBSTRING_INDEX(idskill,'-',-1) > $oldXp AND SUBSTRING_INDEX(idskill,'-',-1) < $newXp AND idskill LIKE '$voie-%'";
        $return = [];
        foreach (sql::fetchAll($qryGetNewSkill) as $value){
            $return[] = $value['name'];
        }
        return $return;
    }

    public function parseAllData(){
        $c = $this->champRecup;
        $c = array_filter($c, function($e) {
            return strpos($e,'name')===false;
        });
        $r = [];
        foreach ($c as $champ){
            if(in_array($champ,$this->champKey)){
                $r[$champ][strtolower($this->get("name$champ"))] = $this->get($champ);
            }else{
                $r[$champ] = $this->get($champ);
            }
        }
        return $r;
    }

    public function getAllSkillPerso($beginIdSkill=null){
        $qry = "SELECT s.idSkill,s.name,s.type,s.description,s.extra,s.cost,sP.id,sP.alias,sP.level FROM perso p
                    INNER JOIN ficheData fdP USING(idPerso)
                    INNER JOIN ficheData fdS USING(idPerso)
                    INNER JOIN skill s
                    left JOIN skillPerso sP USING(idPerso,idSkill)
                    WHERE
                    fdP.label='vPrimaire' AND fdS.label='vSecondaire' AND idPerso='".$this->idPerso."'
                    AND (
                    (idSkill LIKE CONCAT(fdP.value,'%') AND SUBSTRING_INDEX(idskill,'-',-1) < vp)
                    OR
                    (idSkill LIKE CONCAT(fdS.value,'%') AND SUBSTRING_INDEX(idskill,'-',-1) < vs)
                    )";
        if(isset($beginIdSkill) && !empty($beginIdSkill)){
            $beginIdSkillSplit = explode(" ",$beginIdSkill);

            if(count($beginIdSkillSplit) > 1 || !in_array($beginIdSkillSplit[0],$this->getAllVoie())){
                $qry .= " AND (idSkill='".implode("-",$beginIdSkillSplit)."' ";
                $qry .= " OR s.name='$beginIdSkill' OR sP.alias='$beginIdSkill' ";
                unset($beginIdSkillSplit[0]);
                if(!empty($beginIdSkillSplit)){
                    $qry .= " OR s.name='".implode(" ",$beginIdSkillSplit)."' OR sP.alias='".implode(" ",$beginIdSkillSplit)."'";
                }
                $qry .= ")";
            }else{
                $qry .= " AND idSkill like '$beginIdSkill%'";
            }
        }
        $data = ['already'=>[],'empty'=>[]];
        foreach (sql::fetchAll($qry) as $value){
            $line1 = !empty($value['id']) ? 'already' : 'empty';
            $idSkill = explode("-",$value['idSkill']);
            if(!empty($value['extra'])){
                $extra = json_decode($value['extra'],true);
                $value['extra'] = $extra;
                if(isset($extra['up']) && !empty($extra['up'] && $value['level'] > 1)){
                    $value['cost'] = $value['level'] == $extra['up']['max'] ? 'max' : $value['cost']*$value['level'];
                }
            }
            $value['id'] = $idSkill[1];

            $data[$line1][$idSkill[0]][] = $value;
        }
        return $data;
    }

    public function getAllVoie(){
        if(empty($this->allVoie)){
            $diffVoie = array_merge(sql::getJsonBdd("select value from botExtra where label='voieE'"),sql::getJsonBdd("select value from botExtra where label='voieP'"));
            $diffVoie = array_map('strtolower', $diffVoie);
            $this->allVoie = $diffVoie;
        }
        return $this->allVoie;
    }

    public function aliasSkill($id,$alias){
        sql::query("update skillPerso set alias='$alias' where idSkill='$id' and idPerso='".$this->idPerso."'");
        return _t("skill.newAlias",$alias,$id);
    }

    public function upSkill($id){
        $qry = "SELECT cost,name,level,extra FROM skill s LEFT JOIN skillPerso sp ON s.idSkill=sp.idSkill AND sp.idPerso='".$this->idPerso."' where s.idSkill='$id'";
        var_dump($qry);
        $resultSkill = sql::fetch($qry);
        var_dump($resultSkill);
        if(empty($resultSkill['level'])){
            return $this->levelUpSkill($id,true,$resultSkill);
        }

        if(empty($resultSkill['extra'])){
            return _t('skill.max',$resultSkill['name']);
        }

        $extra = json_decode($resultSkill['extra'],true);

        if(!isset($extra['up']) || $extra['up']['max'] <= $resultSkill['level']){
            return _t('skill.max',$resultSkill['name']);
        }

        return $this->levelUpSkill($id,false,$resultSkill);
    }

    public function levelUpSkill($id,$notExist,$skill){
        $pta = $this->get('pta');
        if($pta >= $skill['cost']){
            if($notExist){
                $level = 1;
                sql::query(tools::prepareInsert("skillPerso",[
                    'idPerso' => $this->idPerso,
                    'idSkill' => $id,
                    'level' => $level
                ]));
            }else{
                $level = $skill['level']+1;
                sql::query("update skillPerso set level=$level where idPerso='".$this->idPerso."' and idSkill='$id'");
            }
            $this->update("pta",$pta-$skill['cost']);
            return _t('skill.upSkill',$skill['name'],$level,$this->get('pta'));
        }else{
            return _t('skill.notEnough',$skill['cost']-$pta);
        }
    }
}