<?php
class combat
{

    public function getStatsChar($id_perso = null)
    {
        global $bdd;
        if (empty($id_perso))
        {
            $id_perso = $GLOBALS['id'];
        }

        $statDefault = ['pv' => 200, 'pm' => 20, 'atk' => 20, 'int' => 20];

        $chara = $bdd->query("select niveau,stat from perso p INNER JOIN persoClasse pc ON p.idPerso=pc.idPerso where p.idPerso='$id_perso'")->fetch();

        // Stats bonus
        $statBonus = json_decode($chara['stat'], 'true');

        // race
        $race = $bdd->query("select extra from skill WHERE idSkill= CONCAT('racial-',(SELECT race FROM perso WHERE idPerso='$id_perso'))")->fetch();
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

    public function get_stat($cible, $team)
    {
        global $bdd;
        if ($team == 0)
        {
            $qry = "SELECT c.NAME,c.pv,c.pm,round(m.pv*LEVEL) AS pvM,round(m.pm*LEVEL) AS pmM,round(atk*LEVEL) AS atk FROM combat c INNER JOIN mob m ON SUBSTRING_INDEX(c.name, '-',1)=m.name where m.name='$cible'";
            $res = $bdd->query($qry)->fetch();
            if (empty($res))
            {
                return "error";
            }
            return ['pv' => $res['pv'], 'pm' => $res['pm'], 'atk' => $res['atk']];
        }
        else
        {
            $qry = "select idPerso from perso where prenom like '$cible%'";
            $res = $bdd->query($qry)->fetch();
            if (empty($res))
            {
                return "error";
            }
            return $this->getStatsChar($res['idPerso']);
        }
    }

    // return pv
    public function degat($degat, $cible)
    {
        global $bdd;
        $result = $bdd->query("SELECT pv,team FROM combat WHERE name = '$cible'")->fetch();
        if (empty($result))
        {
            return "error";
        }
        $pvRestants = $result['pv'];

        $stats = $this->get_stat($cible, $result['team']);
        if ($stats == "error")
        {
            return $stats;
        }
        $pvMax = $stats['pv'];

        if ($pvRestants != 0)
        {
            $pvRestants -= $degat;
            $pvRestants = $pvRestants > $pvMax ? $pvMax : ($pvRestants > 0 ? $pvRestants : 0);

            $bdd->query("UPDATE combat SET pv = ROUND('$pvRestants')
                        WHERE name = '$cible'");
            return $pvRestants;
        }
        else
        {
            return false;
        }

    }

    // Début combat
    public function beginEvent()
    {
        global $bdd;
        $test = 1;
        // Récupérer les utilisateurs ayant le role "Event" et remplir la table combat avec leur données
        global $md;
        foreach ($md->getUserWithRole('event') as $cible)
        {
            // var_dump($cible); ## ça te permet de voir le contenu de $cible pour comprendre comment l'utiliser.
            $idUser = $cible->id;
            $nameUser = explode(' ', $cible->username) [0];
            $statsCible = $this->getStatsChar($idUser);
            $array = ["name" => $nameUser, "pv" => $statsCible['pv'], "pm" => $statsCible['pm'], "team" => 1, "level" => 1];
            $bdd->query(Tools::prepareInsert('combat', $array));
        }

    }
}


