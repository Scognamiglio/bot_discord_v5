<?php


class perso
{
    protected $idPerso;
    protected $exist = false;
    protected $allVoie = [];
    protected $champRecup = ['niveau','xp','pSkill','pSkillTotal','pLatent'];
    protected $architect = [
        'fiche' => ['idPerso','race','prenom','sexe','age','niveau','xp','avatar','stats','pSkill','pSkillTotal','pLatent']
    ];
    protected $niveau;
    protected $xp;
    public function __construct($idPerso)
    {
        $this->champRecup = array_merge($this->architect['fiche']); // En prévision de plusieurs sources potentiel.
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
        // Stat défaut
        $perso = sql::fetch("SELECT ".implode(",",$this->architect['fiche'])." FROM perso WHERE idperso='".$this->idPerso."'");
        if(empty($perso)){
            $this->exist = false;
            return;
        }
        $this->exist = true;
        foreach ($this->champRecup as $champ){
            $this->set($champ,$perso[$champ]);
        }

        $idSkills = sql::fetchAll("SELECT SUBSTRING_INDEX(sp.idSkill,'-',1) AS voie,sum(cost*level) as cost FROM skillPerso sp INNER JOIN skill s ON sp.idSkill=s.idSkill WHERE idPerso='".$this->idPerso."' and sp.idSkill NOT LIKE 'racial%' AND sp.idSkill NOT LIKE 'natif%' GROUP BY voie");
        $voies = [];
        foreach ($idSkills as $idSkill){
            if($idSkill['cost'] == 0){
                continue;
            }
            $voies[$idSkill['voie']] = $idSkill['cost'] + ($voies[$idSkill['voie']] ?? 0);
        }
        $this->set('voies',$voies);
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
        return 5 * pow(2,$this->get('niveau'));
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
        $this->update('pSkill',$this->get('pSkill') + $xp);
        $this->update('pSkillTotal',$this->get('pSkillTotal') + $xp);
        $this->update('pLatent',$this->get('pLatent') + ($xp*2));
        return $newXp;
    }


    public function parseAllData(){
        $c = $this->champRecup;
        $c = array_filter($c, function($e) {
            return strpos($e,'name')===false;
        });
        $r = [];
        foreach ($c as $champ){
            $r[$champ] = $this->get($champ);
        }
        return $r;
    }

    public function getAllSkillPerso($beginIdSkill=null){
        $qry = 'select s.idSkill,s.name,s.type,s.description,s.extra,s.cost,sP.id,sP.alias,sP.level
        FROM skill s
        JOIN (
            SELECT SUBSTRING_INDEX(idSkill, "-", 1) AS type, COUNT(1) AS nbr
            FROM skillPerso WHERE idPerso="'.$this->idPerso.'"
            GROUP BY SUBSTRING_INDEX(idSkill, "-", 1)
        ) AS subquery ON SUBSTRING_INDEX(s.idSkill, "-", 1) = subquery.type
        LEFT JOIN skillPerso sP ON sP.idSkill=s.idSkill
        WHERE s.idSkill LIKE CONCAT(subquery.type, "-%")
          AND SUBSTRING_INDEX(SUBSTRING_INDEX(s.idSkill, "-",-2), "-",1) <= subquery.nbr';

        if(isset($beginIdSkill) && !empty($beginIdSkill)){
            $beginIdSkillWithTiret = str_replace(" ","-",$beginIdSkill);
            $qry .= " and (s.idSkill like '%$beginIdSkillWithTiret%' or s.name like '%$beginIdSkill%' or sP.alias like '%$beginIdSkill%')";
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
        $resultSkill = sql::fetch($qry);
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
        $pSkill = $this->get('pSkill');
        if($pSkill >= $skill['cost']){
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
            $this->update("pSkill",$pSkill-$skill['cost']);
            return _t('skill.upSkill',$skill['name'],$level,$this->get('pSkill'));
        }else{
            return _t('skill.notEnough',$skill['cost']-$pSkill);
        }
    }
}