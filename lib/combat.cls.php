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
        foreach (json_decode($chara['stats'], 'true') as $k=>$v){
            $statDefault[$k] += $v;
        }

        // skill
        $result = sql::fetchAll("SELECT extra FROM skillPerso INNER JOIN skill USING (idSkill) WHERE idPerso='$id_perso' AND type='passif'");
        $statSkill = ['pv' => 0, 'pm' => 0, 'atk' => 0, 'int' => 0];
        $passifs = [];
        foreach ($result as $value){
            $json = json_decode($value['extra'],true);
            if(isset($json['stats'])){
                foreach ($json['stats'] as $s=>$v){
                    $statSkill[$s] += (strpos($v, "%") ? (explode("%", $v)[0] * $statDefault[$s] / 100) : $v);
                }
                unset($json['stats']);
            }

            if(!empty($json)){
                foreach ($json as $l=>$v){
                    $passifs[$l] = (isset($passifs[$l]) ? $passifs[$l] : 0)+$v;
                }
            }
        }


        foreach ($statDefault as $k => $v)
        {
            $statDefault[$k] = round(($v + $statSkill[$k]) * (1 + ($chara['niveau'] * 0.4)));
        }

        // Item ??? ///
        $statDefault['passifs'] = $passifs;
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


    // return pv
    public function damage($degat, $cible)
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
        // Récupérer les utilisateurs ayant le role "Event" et remplir la table combat avec leur données
        foreach (ApiDiscord::getUserWithRole('event') as $cible)
        {
            // var_dump($cible); ## ça te permet de voir le contenu de $cible pour comprendre comment l'utiliser.
            $idUser = $cible->id;
            $nameUser = explode(' ', $cible->username) [0];
            $statsCible = $this->getStatsChar($idUser);
            $array = ["name" => $nameUser, "pv" => $statsCible['pv'], "pm" => $statsCible['pm'], "team" => 1, "level" => 1];
            sql::query(Tools::prepareInsert('combat', $array));
        }

    }

    public function getActionTour($team){
        $result = sql::fetchAll("SELECT id,perso,skill,cible FROM action INNER JOIN combat ON perso=NAME WHERE team='$team' and already='0' ORDER BY id");
        if(empty($result)){return false;}

        $return = [];
        foreach ($result as $r){
            if(empty($return[$r['perso']]['stats'])){
                $return[$r['perso']]['stats'] = $this->getStat($r['perso']);
            }
            $return[$r['perso']]['actions'][$r['id']] = [$r['skill'],$r['cible']];
        }
        return $return;

    }


    public function effectiveDamage($degat, $cible){

        $stat = $this->getStat($cible);
        if(!empty($stat['passifs']['protec'])){$degat = $degat * (1-$stat['passifs']['protec']/100);}

        // Todo Gestion des buffs pour réduction de dégâts subis + potentiellement les passifs de voies.

        // Attr + stuff + buff
        $this->damage($degat,$cible);
    }


    public function effectiveAtk ($user,$pui,$data){
        $passif = $user['stats']['passifs'];

        // Passif de voie.
        $checkV = ['voie','typeVoie'];
        $puiBrut = $pui;
        // @TODO Ajouté les différents passif des voies (Si l'effet ne s'applique pas obligatoirement)
        foreach ($checkV as $v){
            if(!empty($passif[$data[$v]])) { $pui += $puiBrut * $passif[$data[$v]] / 100;}
        }
        // item
        // ...



        // @TODO ajouté les buffs qui s'applique ici
        return $pui;
    }

    public function useSkill($user,$pui){
        $idAct = array_keys($user['actions'])[0];
        $act = $user['actions'][$idAct];
        $result = sql::fetch("SELECT extra,label as typeVoie,SUBSTRING_INDEX(idSkill, '-',1) AS voie FROM skill s LEFT JOIN botExtra be ON be.value LIKE CONCAT('%\"',SUBSTRING_INDEX(idSkill, '-',1),'\"%') AND label IN ('voieE','voieA') WHERE s.idSkill='{$act[0]}'");
        $passif = $user['stats']['passifs'];
        $data = ['typeVoie'=>$result['typeVoie'],'voie' => $result['voie']];
        $pui = $this->effectiveAtk($user,$pui,$data);

        $extra = json_decode($result['extra'],true);
        // Applique l'action pour chaque cible
        $already = [];
        foreach (explode(",",$act[1]) as $cible){
            // Foreach pour gérer les actions dans le bon ordre.
            // Généralement, dans extra, si un % est présent, c'est un % du stat en question. Dans le cas contraire, c'est un coef de l'atk effective
            foreach ($extra as $label=>$value){
                $label = strtolower($label);
                // Si déjà exécuté et ne doit pas être relancé.
                if(isset($already[$label]) && $already[$label]){continue;}

                // TODO Rajouté gestion des différents tag dans la colonne extra pour la table skill
                if($label == "dmg"){$this->effectiveDamage($pui*$value,$cible);}
                if($label == "heal"){$this->damage($pui*$value*-1,$cible);}

                // Random cible SELECT name FROM combat ORDER BY RAND() LIMIT 1

                if($label == "selfheal"){
                    $this->damage(round(-1*$value*$pui),$user['name']);
                    $already[$label] = true;
                }
            }

        }

        // @Todo potentiellement gestion d'une action ou d'un buff qui s'applique à la fin de l'action (Récupération de vie si meurtre et ...)
        sql::query("update action set already=1 where id='$idAct' AND 1!=(SELECT VALUE FROM botExtra WHERE label='testSkill')");
    }

}