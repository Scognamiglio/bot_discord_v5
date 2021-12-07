<?php 
/* 
aucun message si pas fct ou exec / this->retour = $parametre > $cherche a acceder a la donnée et send message si plusieur message
*/
class combat
{
    public function getStatsChar($id_perso = null)
    {
        if (empty($id_perso))
        {
            $id_perso = $GLOBALS['id'];
        }

        $statDefault = ['pv' => 200, 'pm' => 20, 'atk' => 20, 'int' => 20];

        $chara = sql::fetch("select niveau,stats from perso where idPerso='$id_perso'");
        // Stats bonus
        $statBonus = json_decode($chara['stats'], 'true');

        // race
        $race = sql::fetch("select extra from skill WHERE idSkill= CONCAT('racial-',(SELECT race FROM perso WHERE idPerso='$id_perso'))");
        $statRace = json_decode($race['extra'], 'true');
        foreach ($statRace as $s => $v)
        {
            if (strpos($v, "%"))
            {
                $statRace[$s] = (explode("%", $v) [0] * $statDefault[$s] / 100);
            }
        }

        // classe
        foreach ($statDefault as $k => $v)
        {
            $statDefault[$k] = ($v + $statBonus[$k] + $statRace[$k]) * $chara['niveau'];
        }

        // Item ??? ///
        return $statDefault;

    }

    public function getStat($cible)
    {
        $qry = "select idPerso from perso where prenom like '$cible%'";
        $res = sql::fetch($qry);
        if(!empty($res)){
            return $this->getStatsChar($res['idPerso']);
        }else{
            $qry = "SELECT c.NAME,c.pv,c.pm,round(m.pv*LEVEL) AS pvM,round(m.pm*LEVEL) AS pmM,round(atk*LEVEL) AS atk FROM combat c INNER JOIN mob m ON SUBSTRING_INDEX(c.name, '-',1)=m.name where c.name='$cible'";
            $res = sql::fetch($qry);
            if (empty($res)) {return "error";}
            return ['pv' => $res['pv'], 'pm' => $res['pm'], 'atk' => $res['atk']];
        }

    }

    // return pv
    public function degat($degat, $cible)
    {
        $result = sql::fetch("SELECT pv,team FROM combat WHERE name = '$cible'");

        if (empty($result)) {return "error";}
        $pvRestants = $result['pv'];

        $stats = $this->getStat($cible);

        if ($stats == "error") {return $stats;}
        $pvMax = $stats['pv'];

        if ($pvRestants != 0)
        {
            $pvRestants -= $degat;
            $pvRestants = $pvRestants > $pvMax ? $pvMax : ($pvRestants > 0 ? $pvRestants : 0);

            sql::query("UPDATE combat SET pv = ROUND('$pvRestants') WHERE name = '$cible'");
            return $pvRestants;
        }
        return false;

    }

    // Début combat
    public function beginEvent()
    {
        global $md;
        // Récupérer les utilisateurs ayant le role "Event" et remplir la table combat avec leur données
        foreach ($md->getUserWithRole('event') as $cible)
        {
            // var_dump($cible); ## ça te permet de voir le contenu de $cible pour comprendre comment l'utiliser.
            $idUser = $cible->id;
            $nameUser = explode(' ', $cible->username) [0];
            $statsCible = $this->getStatsChar($idUser);
            $array = ["name" => $nameUser, "pv" => $statsCible['pv'], "pm" => $statsCible['pm'], "team" => 1, "level" => 1];
            sql::query(Tools::prepareInsert('combat', $array));
        }

    }
    /**
     * récupérer les statistique de tout les participants en les regroupant par team dans un tableau 
     */
    public function getStatsAll() {
        $content = sql::fetchAll("SELECT * FROM combat ORDER BY team");
        $tabAff = [];
        foreach ($content as $cible) {            
            $tabAff[$cible["team"]][$cible["name"]] = ['pv' => $cible['pv'], 'pm' => $cible['pm'], 'lvl' => $cible['level']];            
        }
        return $tabAff;
    }
}


