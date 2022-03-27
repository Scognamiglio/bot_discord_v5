<?php


class perso
{
    protected $idPerso;
    protected $exist = false;
    protected $champRecup = ['niveau','xp','VP','VS','nameVP','nameVS'];
    protected $champKey = ['VP','VS'];
    protected $architect = [
        'fiche' => ['niveau','xp','VP','VS']
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
        $perso = sql::fetch("SELECT niveau,xp,VP,VS,vp.value AS nameVP,vs.value AS nameVS
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
}