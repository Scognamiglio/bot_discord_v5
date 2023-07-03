<?php
/* _commande sont ignoré par bot*/
class fctAdmin extends structure {

    public function __construct()
    {
        $this->required = "admin";
    }

    public function stop($param){
        $_SESSION['continue']=false;
        ApiDiscord::sendMessage("Bonne nuit <3");
        sleep(1);
        $this->md->get('discord')->close();
    }

    // don't work
    public function run($param){
        ApiDiscord::sendMessage("Bonne nuit <3");
        sleep(1);
        $this->md->get('discord')->close();
    }

    public function send($param){
        preg_match_all("/([^ ]*) {([^}]*)}(.*)/s",$param,$array);
        $idCible = $array[1][0];
        $title = $array[2][0];
        $newMsg = $array[3][0];

        $chara = sql::fetch("select prenom,avatar from perso where idPerso='{$this->id}'");
        $sqlt = [
            'Author' => $chara['prenom'],
            'Thumbnail' => $chara['avatar'],
            'Title' => $title,
            "Description" => $newMsg,
            "Color" => "0x00AE86"
        ];
        $this->md->sendPrivateMessage($idCible,'',$sqlt);
    }


    public function hook($param){
        ApiDiscord::createHook($this->message->channel->id);
    }

    public function event($param){
        global $cb;
        $cb->beginEvent();
        return _t('event.begin');
    }

    public function degat($param){
        global $cb;
        $param = explode(" ",$param);
        if(count($param) != 2){
            $msg = _t('errorParam',2);
        }else{
            $result = $cb->damage($param[1],$param[0]);
            if(false===$result){
                $msg = _t('degat.alreadyKill',$param[0]);
            }elseif($result===0){
                $msg = _t('degat.Kill',$param[0]);
            }elseif($result=="error"){
                $msg = _t('error');
            }else{
                $msg = _t('degat.success',$result,$param[0]);
            }
        }
        return $msg;

    }

    public function mob($param)
    {
        $param = explode(" ",$param);
        if(count($param) != 2){return $this->help("mob");}

        $qry = "select pv,pm from mob where name='{$param[0]}'";
        $result = sql::fetch($qry);
        if(empty($result)){return _t('mob.error');}


        $qry = "select count(1) as c from combat where name like '{$param[0]}%'";
        if(sql::fetch($qry)['c']){
            $param[0] .= "-".sql::fetch($qry)['c'];
        }

        $tab = [
            'name' => $param[0],
            'pv' => $result['pv'],
            'pm' => $result['pm'],
            'team' => 0,
            'level' => $param[1]
        ];
        sql::query(Tools::prepareInsert('combat',$tab));
        return _t('mob.success',$param[0]);
    }

    /**
     * renvoit les statistiques de tous les entités présente dans l'évent
     * ! attention les nom apparaissent avec une majuscule meme si dans la bdd ce n'est pas le cas > pour l'instnant la bdd n'est pasd sensible a la casse donc c'est ok
     * TODO : recuperer les stat d'une personne ou d'un groupe
     * TODO : faire un message si il n'y a pas d'évent
     */
    public function stats($param = null)
    {
        global $cb;
        if (empty($param)) {
            $msg = $cb->getStatsAll();
            var_dump($msg);

            $format = [
                'life' => '%s [%s PV][%s PM]', // Si en vie
                'KO' => '%s'
            ];

            $sqlt = [
                'Title' => ">> Stats",
                "FieldValues" => [],
                "Color" => "0x00AE86",
            ];

            foreach($msg as $team=>$array){
                $sqlt['FieldValues'][] = ["", "> **__Team $team __**",false];
                $ret = [];
                array_map(function($f) use($format,&$ret){
                    $state = $f['pv'] > 0 ? 'life' : 'KO';
                    $ret[$state][] = sprintf($format[$state],$f['name'],$f['pv'],$f['pm']);
                },$array['perso']);
                if(isset($ret['life'])){
                    $sqlt['FieldValues'][] = ["En état","```md\n".implode("\n",$ret['life'])."\n```",'inline'];
                }
                if(isset($ret['KO'])){
                    $sqlt['FieldValues'][] = ["Ko","```ml\n".implode("\n",$ret['KO'])."\n```",'inline'];
                }
                if(isset($array['buff'])){
                    $listBuff = [];
                    array_map(function($f) use(&$listBuff){$listBuff[] = sprintf('%s [%s][%s Tours][%s]',$f['label'],$f['cible'],$f['nbrTour'],$f['modificateur']);},$array['buff']);

                    $sqlt['FieldValues'][] = ["Effet","```md\n".implode("\n",$listBuff)."\n```",0];
                }
            }
            return $sqlt;

            //return $sqlt;
            return "maintenance";
            $equipe = "";
            $vivants = "";
            $ko = "";
            $ret = "";
            $curseur = array_keys($msg);
            $i = 0;
            foreach ($msg as $element) {
                $equipe .= "\n**<Equipe ".($curseur[$i]+1). " >**\n\n";
                $i++;
                foreach ($element as $key => $value) {
                    ($value['pv'] > 0) ?
                        $vivants .= "<" . ucfirst($key) . "> [" . $value['pv'] . " PV]" . "[" . $value['pm'] . " PM]\n"
                        :
                        $ko .=  ucfirst($key) . "\n";
                }
                $equipe .= ($ko !== "") ? "**KO :**\n```ml\n$ko```\n" : "Aucun KO\n\n";
                $ko = "";
                $equipe .= ($vivants !== "") ? "**En état de combattre :\n**```md\n$vivants```" : "Aucun survivant, dommage...\n";
                $vivants = "";
                $ret .= $equipe . "\n";
                $equipe = ""; 
            }
            return  "**__Rapport__**\n$ret";
        }
    }

    public function topic($param)
    {
        $id = explode(" ",$param)[0];
        $member = $this->md->getUserbyId($id);
        if(empty($member)){return "user $id inconnu";}
        $p = [['id'=>$id,'type'=>1,'allow'=>68608,'deny' => 0]];
        $nom = explode(" ",$member->username)[0];
        $category = ApiDiscord::createTopic("Dojo $nom",4,null,$p);
        ApiDiscord::createTopic("Chambre $nom",0,$category['id']);
    }

    public function tour($param){
        global $cb;
        $team = trim(explode("```",$param)[0]);
        // Si 0, alors tour pnj.
        if($team === "0"){
            return $this->_tourPnj($param);
        }
        $actions = $cb->getActionTour($team);
        if(empty($actions)) {return _t('tour.empty',$team);} //Todo gérer tour vide

        preg_match_all("/\[([^]]*)\] ?(?:\(([^)]*)\))?/s",$param,$actTour);
        $nbrAction = count($actTour[0]);
        $error = [];
        $cb->effetTour($team,0);
        for ($i=0;$i<$nbrAction;$i++){
            $userName = $actTour[1][$i];
            if(empty($actions[$actTour[1][$i]]['actions'])){$error[] = _t('tour.notExist',$actTour[1][$i]);continue;}
            $user = $actions[$userName];
            $user['name'] = $userName;
            $modificateur = trim(empty($actTour[2][$i]) ? 0 : $actTour[2][$i]);
            $pui = $user['stats']['atk'];
            $pui = strpos($modificateur,"%") ? ($pui/100)*substr($modificateur,0,-1) : $pui+$modificateur;

            $cb->useSkill($user,$pui);
            $idAct = array_keys($actions[$userName]['actions'])[0];
            unset($actions[$userName]['actions'][$idAct]);
        }
        $cb->effetTour(null,3);
        return $this->stats();

    }

    public function _tourPnj($param){
        global $cb;
        preg_match_all("/\[([^]]*)\] ?(?:\(([^)]*)\))? ?(?:\(([^)]*)\))?/s",$param,$actTour);
        $nbrAction = count($actTour[0]);
        for ($i=0;$i<$nbrAction;$i++){
            $cibles = explode(",",$actTour[1][$i]);
            foreach($cibles as $cible){
                $cb->cible = $cible;
                if(!empty($actTour[2][$i])){
                    $cb->effectiveDamage($actTour[2][$i],$cb->cible);
                }
                var_dump($actTour[3]);
                if(!empty($actTour[3][$i])){
                    $cb->addEffect(json_decode($actTour[3][$i]));
                }
            }
        }

        return $this->stats();
    }

    public function trad($param){
        var_dump(_t($param));
        var_dump(_t($param,'a'));
        var_dump(_t($param,'a','v'));
    }

    public function percist($param){
        $this->delete = true;
        $id = preg_split( "/[ \n]/", $param )[0];
        $texte = str_replace("$id","",$param);
        $already = sql::fetch("select value from botExtra where label='percistMsg-$id'")['value'] ?? false;

        if(empty($texte)){
            if(!$already){return _t('percist.error',$id);}
            $dataSplit = explode('|',$already);
            $msg = ApiDiscord::getMessage($dataSplit[0],$dataSplit[1])[$dataSplit[1]];
            $msg = str_replace("> **__{$id}__**\n","",$msg);
            return "```\n$msg```";
        }


        $texte = "> **__{$id}__**\n\n".substr($texte,1);
        if($already==false){
            $json = apiDiscord::sendMessage($texte);
            sql::query("insert into botExtra values('percistMsg-$id','{$json['channel_id']}|{$json['id']}')");
        }else{
            $dataSplit = explode('|',$already);
            apiDiscord::editMessage($dataSplit[0],$dataSplit[1],$texte);
        }
    }

}