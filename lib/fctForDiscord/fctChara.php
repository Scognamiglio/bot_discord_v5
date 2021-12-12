<?php

class fctChara extends structure {

    public function __construct()
    {
        $this->required = "fiche";
    }


    public function fiche($param){
        global $cb;

        $chara = sql::fetch("SELECT prenom,avatar,niveau,xp,race,sexe,fd1.value as vpn,vp,fd2.value as vsn,vs FROM perso p left JOIN ficheData fd1 ON p.idPerso=fd1.idPerso AND fd1.label='vPrimaire' left JOIN ficheData fd2 ON p.idPerso=fd2.idPerso AND fd2.label='vSecondaire' WHERE p.idPerso='{$this->id}'");
        $stats = $cb->getStatsChar();
        $sqlt = [
            'Author' => $chara['prenom'],
            'Thumbnail' => $chara['avatar'],
            'Title' => "Niveau : {$chara['niveau']}\n",
            "Description" => "> **__Informations générales__**\n\n**Experience** : {$chara['xp']}/1200\n**Race** : {$chara['race']}\n**Sexe** : {$chara['sexe']}\n\n> __**Voies**__\n\n**Voie primaire** : {$chara['vpn']} (niv. {$chara['vp']})\n**Voie secondaire** : {$chara['vsn']} (niv. {$chara['vs']})\n\n> **__Statistiques__**",
            "FieldValues" => [
                ["PV : {$stats['pv']}", "**ATK : {$stats['atk']}**","inline"],
                ["PM : {$stats['pm']}", "**INT : {$stats['int']}**","inline"]
            ],
            "Color" => "0x00AE86"
        ];
        return $sqlt;

    }

    public function pnj($param){
        $data = $this->_TraitementData($param,['alias','nom','image']);
        if(count($data)!=3){return $this->help("pnj");}

        $qry = "insert into pnj values ('{$data['alias']}','{$data['nom']}','{$data['image']}','{$this->id}') ON DUPLICATE KEY UPDATE name='{$data['nom']}',img='{$data['image']}'";
        sql::query($qry);
        return "Le PNJ a été créé ou mis à jour";
    }

    public function lock($param){
        $pId = $this->message->channel->parent_id;
        $category = $this->md->getChannelById($pId);
        $user = $this->md->getUserbyId($this->id);
        $nom = explode(" ",$user->username)[0];
        if($category==null || strtolower("dojo ".$nom) != strtolower($category->name)) {return "Vous ne pouvez pas fermer cette zone.";}

        $idUse = $this->md->getRoleId('rp');
        $perm = null;
        foreach ($category->permission_overwrites as $c){
            if($idUse == $c->id){
                $perm = $c;break;
            }
        }
        $isLock = $perm->deny=='1024';
        $permSet = [
            'id' => "$idUse",
            'type' => '0',
            'allow' => ($isLock ? '68608' : '67584'),
            'deny' => ($isLock ? '0' : '1024'),
        ];

        ApiDiscord::ChangePerm($pId,$permSet);
    }

    public function skill($param){
        global $id;
        preg_match_all("/^([^\r\n(]*)(?:\(([^)]*)\)?)? ?(?:\[([^\]]*)\]?)?/s",$param,$array);
        $attaquant = null;
        if(empty($array)){return "Commande mal formulé";}
        if($this->isAdmin){
            $attaquant = empty($array[3][0]) ? null : tools::sansAccent(strtolower(trim($array[3][0])));
        }

        if($attaquant){
            $user = sql::fetch("select pm,name from combat where name='$attaquant'");
            if(empty($user)) {return "attaquant non connu.";}
        }else{
            $user = sql::fetch("SELECT pm,name FROM perso INNER JOIN combat ON SUBSTRING_INDEX(prenom, ' ',1)=name WHERE idPerso='{$this->id}'");
            if(empty($user)) {return "Tu n'es pas dans le combat";}
        }
        $cible =  tools::sansAccent(strtolower(trim($array[1][0])));
        $skill = empty($array[2][0]) ? 'Attaque' : tools::sansAccent(strtolower(trim($array[2][0])));
        if(empty(sql::fetch("select 1 from combat where name='$cible'"))){return "La cible $cible est inconnu";}

        if($attaquant){
            $rs = sql::fetch("select idSkill,extra from skill where name='$skill'");
        }else{
            $rs = sql::fetch("SELECT idSkill,extra FROM skillPerso INNER JOIN skill USING(idSkill) WHERE idPerso='{$this->id}' AND (alias ='$skill' OR NAME='$skill')");
        }

        if(empty($rs)){return "Skill non connu.";}
        $extra = json_decode($rs['extra'],true);
        if(!empty($extra['cost']) && $extra['cost'] > $user[0]){return "Il manque ".($extra['cost']-$user[0])." pm pour lancer le sort";}
        if(!empty($extra['cost'])){
            $pm = $user[0]-$extra['cost'];
            if($pm < 0){return "Il manque $pm pm pour lancer le sort";}
            sql::query("update combat set pm='$pm' where name='{$user[1]}'");
        }

        sql::query(tools::prepareInsert("action",['perso'=>$user[1],'skill'=>$rs['idSkill'], 'cible'=>$cible]));
    }
}