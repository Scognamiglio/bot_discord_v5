<?php

class fctChara extends structure {

    public function __construct()
    {
        $this->required = "fiche";
    }

    public function _ficheFull($idUse){
        $structFicheFull = [
            'infoG' => [
                'name' => "**__%s__** : %s",
                'genre' => "**__%s__** : %s",
                'age' => "**__%s__** : %s",
            ],
            'perso' => [
                'caractere' => "**__%s__** : \n%s",
                'objectif' => "**__%s__** : \n%s",
            ],
            'story' => 'withChap',

            'don' => 'withChap',

            'descriptionPhysique' => [
                'physique' =>"**__%s__** : \n%s\n",
                'image' => '**__%s__** : %s'
            ]
        ];
        $allData = sql::createArrayOrder("select label,value from ficheData where idPerso='$idUse'",'label');
        $messages = [];
        foreach ($structFicheFull as $cat => $array){
            $messages[] = "\n"._t("ficheFull.$cat")."\n\n";
            if( $array == 'withChap' ){
                array_walk($allData,function ($v,$l) use($cat,&$messages,$allData){
                    if ( strpos( $l, "ext-$cat") ){
                        $messages[] = "**__".$allData[str_replace('text','title',$l)]."__**\n```".$v."```\n";
                    }
                });
                continue;
            }
            foreach ($array as $label=>$format){
                $messages[] = sprintf($format,_t("ficheFull.$label"),$allData[$label])."\n";
            }
        }
        ApiDiscord::sendLongMessage($messages);
    }


    public function fiche($param){
        global $cb;

        $data = $this->_TraitementData($param,['id','full']);
        $idUse = ($this->isAdmin && isset($data['id'])) ? $data['id'] : $this->id;

        if(isset($data['full'])){
            $this->_ficheFull($idUse);
            return false;
        }
        $stats = $cb->getStatsChar();
        $perso = new perso($idUse);
        $texteVoie = '';
        foreach ($perso->get('voies') as $v=>$xp){
            $texteVoie.="**$v** : $xp\n";
        }
        $sqlt = [
            'Author' => $perso->get('prenom'),
            'Thumbnail' => $perso->get('avatar'),
            'Title' => _t('fiche.niveau')." : {$perso->get('niveau')}\n",
            "Description" => "> **__"._t('fiche.info')."__**\n\n**"._t('fiche.xp')."** : {$perso->get('xp')}/1200\n**"._t('fiche.race')."** : {$perso->get('race')}\n**"._t('fiche.sexe')."** : {$perso->get('sexe')}\n\n> __**"._t('fiche.voie')."**__\n\n$texteVoie\n\n> **__"._t('fiche.stats')."__**",
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
            if(empty($user)) {return _t('action.who');}
        }else{
            $user = sql::fetch("SELECT pm,name FROM perso INNER JOIN combat ON SUBSTRING_INDEX(prenom, ' ',1)=name WHERE idPerso='{$this->id}'");
            if(empty($user)) {return _t('action.notInBattle');}
        }
        $skill = empty($array[2][0]) ? 'Attaque' : tools::sansAccent(strtolower(trim($array[2][0])));

        if($attaquant){
            $rs = sql::fetch("select idSkill,extra from skill where name='$skill'");
        }else{
            $rs = sql::fetch("SELECT idSkill,extra FROM skillPerso INNER JOIN skill USING(idSkill) WHERE idPerso='{$this->id}' AND (alias ='$skill' OR NAME='$skill')");
        }

        if(empty($rs)){return _t('action.unknown');}
        $extra = json_decode($rs['extra'],true);
        if(!empty($extra['costPM']) && $extra['costPM'] > $user[0]){return _t('action.lost',($extra['costPM']-$user[0]));}
        $nbrCible = empty($extra['nbr']) ? 1 : $extra['nbr'];
        if(is_numeric($nbrCible)){
            $cibles =  explode(",",tools::sansAccent(strtolower(trim($array[1][0]))));
            if(count($cibles) != $nbrCible){return _t('action.nbr',$nbrCible);}
            foreach ($cibles as $cible){
                if(empty(sql::fetch("select 1 from combat where name='$cible'"))){return _t('action.unknownCible',$cible);}
            }
            $dataCible = implode(",",$cibles);
        }else{
            $dataCible = $nbrCible;
        }

        if(!empty($extra['costPM'])){
            $pm = $user[0]-$extra['costPM'];
            sql::query("update combat set pm='$pm' where name='{$user[1]}'");
        }

        sql::query(tools::prepareInsert("action",['perso'=>$user[1],'skill'=>$rs['idSkill'], 'cible'=>$dataCible]));
    }

    public function train($param)
    {
        //get id lanceur
        global $id;
        $addXP = 5;
        // get date du jour
        $dateFormatte = date("d/m/Y");
        $response = sql::fetch("SELECT * FROM entrainement WHERE id = '$id' AND jourEntrainement = CURRENT_DATE()");
        if (!empty($response)) {
            $again = (24-date("h"))." heure(s) et ".(60-date('i'))." minute(s)";
            return _t('train.again',$again);
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
            $retour[] = ($xpForUp) ? _t('train.againXp',$xpForUp-$newXp) : _t('train.max');
            $retour[] = _t("train.pLatent",$perso->get('pLatent'));
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
        $nbr = 0;
        $myId = "";
        array_map(function($f) use(&$nbr,&$myId){
            array_map(function($f2) use(&$nbr,&$myId){
                $nbr += count($f2);
                $myId = $f2[0]['idSkill'];
            },$f);
        },$allSkill);
        $one = $nbr < 4;
        if(!empty($act)){
            $actParam = $act[array_keys($act)[0]];
            $act = strtolower(array_keys($act)[0]);
            if($nbr != 1){
                return _t('skill.errorToSkill');
            }
            if(!empty($allSkill['empty']) && $act == 'alias'){
                return _t('skill.noSkillForAlias');
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
                    $retour .= " **__{$skill['name']}__** ({$skill['idSkill']})\n```xml\n";
                    if($one){
                        $retour.= "<"._t('skill.info').">\n";
                        $retour.= _t('skill.already'). ($line1=="empty" ? "Non" : "Oui")."\n";
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