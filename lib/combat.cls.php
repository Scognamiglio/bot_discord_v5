<?php
class combat
{

    public function get_stat($id_perso = null)
    {
        global $bdd;
        if (empty($id_perso)) {
            $id_perso = $GLOBALS['id'];
        }

        $statDefault = [
            'pv' => 200,
            'pm' => 20,
            'atk' => 20,
            'int' => 20
        ];

        $chara = $bdd->query("select niveau,stat from perso p INNER JOIN persoClasse pc ON p.idPerso=pc.idPerso where p.idPerso='$id_perso'")->fetch();

        // Stats bonus
        $statBonus = json_decode($chara['stat'],'true');

        // race
        $race = $bdd->query("select extra from skill WHERE idSkill= CONCAT('racial-',(SELECT race FROM perso WHERE idPerso='$id_perso'))")->fetch();
        $statRace = json_decode($race['extra'],'true');
        foreach ($statRace as $s=>$v){
            if(strpos($v,"%")){
                $statRace[$s] = (explode("%",$v)[0] * $statDefault[$s] / 100);
            }
        }

        // classe

        foreach ($statDefault as $k=>$v){
            $statDefault[$k] = ($v + $statBonus[$k] + $statRace[$k]) * $chara['niveau'];
        }

        // Item ??? ///

        return $statDefault;

    }
}