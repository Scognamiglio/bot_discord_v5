<?php
/* _commande sont ignoré par bot*/
class fctAdmin extends structure {

    public function __construct()
    {
        $this->required = "admin";
    }

    public function stop($param){
        $_SESSION['continue']=false;
        $this->message->channel->sendMessage("Bonne nuit <3");
        sleep(1);
        $this->md->get('discord')->close();
    }

    // don't work
    public function run($param){
        $this->message->channel->sendMessage("Bonne nuit <3");
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
            $result = $cb->degat($param[1],$param[0]);
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

    public function stats($param = null)
    {
        global $cb;
        if (empty($param)) {
            $msg = $cb->getStatsAll();
            $ret = $msg[1]["Zheneos"]["pv"];
            $this->message->channel->sendMessage($ret);
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
        $actions = $cb->getActionTour($team);
        if(empty($actions)) {return _t('tour.empty',$team);}

        preg_match_all("/\[([^]]*)\] ?(?:\(([^)]*)\))?/s",$param,$actTour);
        $nbrAction = count($actTour[0]);
        $error = [];
        for ($i=0;$i<$nbrAction;$i++){
            $userName = $actTour[1][$i];
            if(empty($actions[$actTour[1][$i]]['actions'])){$error[] = _t('tour.notExist',$actTour[1][$i]);continue;}
            $user = $actions[$userName];
            $user['name'] = $userName;
            $coef = trim(empty($actTour[2][$i]) ? 0 : $actTour[2][$i]);
            $pui = $user['stats']['atk'];
            $pui = strpos($coef,"%") ? ($pui/100)*substr($coef,0,-1) : $pui+$coef;

            $cb->useSkill($user,$pui);
            $idAct = array_keys($actions[$userName]['actions'])[0];
            unset($actions[$userName]['actions'][$idAct]);
        }

        // Rajouté le rapport créer par Vymarel-Sama

    }

    public function trad($param){
        var_dump(_t($param));
        var_dump(_t($param,'a'));
        var_dump(_t($param,'a','v'));
    }
}