<?php


class perso
{
    protected $idPerso;
    protected $exist = false;
    protected $champRecup = ['niveau','xp'];
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

    // Récupére data importante.
    public function getData(){
        $perso = sql::fetch("select ".implode(",",$this->champRecup)." from perso where idperso='".$this->idPerso."'");
        if(empty($perso)){
            $this->exist = false;
            return;
        }
        $this->exist = true;
        foreach ($this->champRecup as $champ){
            $this->set($champ,$perso[$champ]);
        }
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
        sql::query("update perso set xp=$newXp where idperso='".$this->idPerso."'");
        $this->set('xp',$newXp);
        return $newXp;
    }

    public function levelUPChar($newLevel){
        if(!$this->exist){return false;}
        sql::query("update perso set niveau=$newLevel where idperso='".$this->idPerso."'");
        $this->set('niveau',$newLevel);
        return true;
    }

    // Formule d'xp temporaire.
    public function xpForLevelUp(){
        var_dump($this->get('niveau'));
        if($this->get('niveau') >= 5){
            return false;
        }
        return 50 * pow(2,$this->get('niveau'));
    }




}