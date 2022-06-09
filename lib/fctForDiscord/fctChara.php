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
            'Title' => _t('fiche.niveau')." : {$chara['niveau']}\n",
            "Description" => "> **__"._t('fiche.info')."__**\n\n**"._t('fiche.xp')."** : {$chara['xp']}/1200\n**"._t('fiche.race')."** : {$chara['race']}\n**"._t('fiche.sexe')."** : {$chara['sexe']}\n\n> __**"._t('fiche.voie')."**__\n\n**"._t('fiche.vP')."** : {$chara['vpn']} (niv. {$chara['vp']})\n**"._t('fiche.vS')."** : {$chara['vsn']} (niv. {$chara['vs']})\n\n> **__"._t('fiche.stats')."__**",
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
        return _t('pnj.success');
    }

    public function lock($param){
        $pId = $this->message->channel->parent_id;
        $category = $this->md->getChannelById($pId);
        $user = $this->md->getUserbyId($this->id);
        $nom = explode(" ",$user->username)[0];
        if($category==null || strtolower("dojo ".$nom) != strtolower($category->name)) {return _t('lock.error');}

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

    public function action($param){
        global $id;
        preg_match_all("/^([^\r\n(]*)(?:\(([^)]*)\)?)? ?(?:\[([^\]]*)\]?)?/s",$param,$array);
        $attaquant = null;
        if(empty($array)){return $this->help("action");}
        if($this->isAdmin){
            $attaquant = empty($array[3][0]) ? null : tools::sansAccent(strtolower(trim($array[3][0])));
        }

        if($attaquant){
            $user = sql::fetch("select pm,name from combat where name='$attaquant'");
            if(empty($user)) {return _t('skill.who');}
        }else{
            $user = sql::fetch("SELECT pm,name FROM perso INNER JOIN combat ON SUBSTRING_INDEX(prenom, ' ',1)=name WHERE idPerso='{$this->id}'");
            if(empty($user)) {return _t('skill.notInBattle');}
        }
        $skill = empty($array[2][0]) ? 'Attaque' : tools::sansAccent(strtolower(trim($array[2][0])));

        if($attaquant){
            $rs = sql::fetch("select idSkill,extra from skill where name='$skill'");
        }else{
            $rs = sql::fetch("SELECT idSkill,extra FROM skillPerso INNER JOIN skill USING(idSkill) WHERE idPerso='{$this->id}' AND (alias ='$skill' OR NAME='$skill')");
        }

        if(empty($rs)){return _t('skill.unknown');}
        $extra = json_decode($rs['extra'],true);
        if(!empty($extra['cost']) && $extra['cost'] > $user[0]){return _t('skill.lost',($extra['cost']-$user[0]));}

        $nbrCible = empty($extra['nbr']) ? 1 : $extra['nbr'];
        if(is_numeric($nbrCible)){
            $cibles =  explode(",",tools::sansAccent(strtolower(trim($array[1][0]))));
            if(count($cibles) != $nbrCible){return _t('skill.nbr',$nbrCible);}
            foreach ($cibles as $cible){
                if(empty(sql::fetch("select 1 from combat where name='$cible'"))){return _t('skill.unknownCible',$cible);}
            }
            $dataCible = implode(",",$cibles);
        }else{
            $dataCible = $nbrCible;
        }

        if(!empty($extra['cost'])){
            $pm = $user[0]-$extra['cost'];
            sql::query("update combat set pm='$pm' where name='{$user[1]}'");
        }

        sql::query(tools::prepareInsert("action",['perso'=>$user[1],'skill'=>$rs['idSkill'], 'cible'=>$dataCible]));
    }

    public function train($param)
    {
        //get id lanceur
        global $id;
        $addXP = 50;
        $addVoie = 5;
        // get date du jour
        $dateFormatte = date("d/m/Y");
        $response = sql::fetch("SELECT * FROM entrainement WHERE id = '$id' AND jourEntrainement = CURRENT_DATE()");
        if (!empty($response)) {
            $again = (24-date("h"))." heure(s) et ".(60-date('i'))." minute(s)";
            return _t('train.again',$again);
        }
        if(empty($param)){
            return $this->help('train');
        }
        $trainPossible = [];
        foreach (sql::fetchAll("SELECT value FROM ficheData WHERE idPerso='$id' AND label IN('vPrimaire','vSecondaire')") as $r){
            $trainPossible[] = strtolower($r[0]);
        }
        $param = strtolower($param);
        if(!in_array($param,$trainPossible)){
            return _t('train.errorType',implode("\n- ",$trainPossible));
        }


        $retour = [];
        $perso = new perso($id);
        $newXp = $perso->addXp($addXP);
        if($newXp === 'max'){
            $retour[] = _t('train.max');
        }else{
            if($newXp < $addXP){
                $retour[] = _t('train.levelUP',$perso->get('niveau'));
            }
            $xpForUp = $perso->xpForLevelUp();
            if($xpForUp){
                $retour[] = _t('train.againXp',$xpForUp-$newXp);
            }else{
                $retour[] = _t('train.max');
            }
        }
        $newSkill = $perso->addXpVoie($addVoie,$param);
        if(!empty($newSkill)){
            $retour[] = _t('train.newSkill',implode(',',$newSkill));
        }


        sql::query("INSERT INTO entrainement(id,jourEntrainement) VALUES($id,CURRENT_DATE()) ON DUPLICATE KEY UPDATE id='$id',jourEntrainement=CURRENT_DATE()");
        return _t('train.return',$dateFormatte,implode('\n',$retour));
    }

    public function skill($param)
    {
        global $id;
        $fields = ['alias','type','level','cost'];
        $perso = new perso($id);

        $act = $this->_TraitementData($param,['alias','up']);
        if(count($act) > 1){
            return _t('skill.errorToParam');
        }

        $nameSkill = trim(!empty($act) ? explode("-".array_keys($act)[0],$param)[0] : $param);

        $allSkill = $perso->getAllSkillPerso($nameSkill);
        $retour = null;

        $beginIdSkillSplit = explode(" ",$nameSkill);
        $one = count($beginIdSkillSplit) > 1 || (!empty($beginIdSkillSplit[0]) && !in_array($beginIdSkillSplit[0],$perso->getAllVoie()));
        if(!empty($act)){
            $actParam = $act[array_keys($act)[0]];
            $act = strtolower(array_keys($act)[0]);
            if(!$one){
                return _t('skill.errorToSkill');
            }
            $myId = null;
            if(!empty($allSkill['already'])){
                $myId = $allSkill['already'][array_keys($allSkill['already'])[0]][0]['idSkill'];
            }
            if(!empty($allSkill['empty'])){
                if($act == 'alias'){
                    return _t('skill.noSkillForAlias');
                }
                $myId = $allSkill['empty'][array_keys($allSkill['empty'])[0]][0]['idSkill'];
            }
            return empty($myId) ? _t('skill.notFound') : $perso->{$act."Skill"}($myId,$actParam);
        }
        foreach ($allSkill as $line1=>$array){
            if(empty($array)){
                continue;
            }
            if(!$one) {$retour .= "> __**"._t("skill.$line1")."**__\n\n";}
            foreach ($array as $voie=>$skills){
                if(empty($nameSkill)) {$retour .= _t('skill.voie')." : ***".$voie."***\n";}
                foreach ($skills as $skill){
                    $retour .= $one ? '>' : '-';
                    $retour .= " **__{$skill['name']}__** ({$skill['id']})\n```xml\n";
                    if($one){
                        $retour.= "<"._t('skill.info').">\n";
                        $retour.= _t('skill.already')." : ". ($line1=="empty" ? "Non" : "Oui")."\n";
                    }
                    foreach ($fields as $field){
                        if(!empty($skill[$field])){
                            $retour .= _t("skill.$field")." : ".$skill[$field]."\n";
                        }
                    }
                    if($one){
                        $retour.= "\n<"._t('skill.description').">\n";
                        $retour .= $skill['description']."\n";
                    }
                    $retour .= "```\n";
                }
            }
        }

        return $retour ?? _t('skill.notFound');

    }
}