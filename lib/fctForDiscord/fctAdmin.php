<?php
class fctAdmin extends structure {

    public function __construct()
    {
        $this->required = "admin";
    }

    public function repeat($param){
        $this->message->channel->sendMessage($param);
    }

    public function stop($param){
        $_SESSION['continue']=false;
        $this->message->channel->sendMessage("Bonne nuit <3");
        sleep(1);
        $this->md->get('discord')->close();
    }

    public function send($param){
        global $bdd;
        preg_match_all("/([^ ]*) {([^}]*)}(.*)/s",$param,$array);
        $idCible = $array[1][0];
        $title = $array[2][0];
        $newMsg = $array[3][0];

        $chara = $bdd->query("select * from perso p INNER JOIN persoClasse pc ON p.idPerso=pc.idPerso where p.idPerso='{$this->id}'")->fetch();
        $sqlt = [
            'Author' => $chara['prenom'],
            'Thumbnail' => $chara['avatar'],
            'Title' => $title,
            "Description" => $newMsg,
            "Color" => "0x00AE86"
        ];
        $this->md->sendPrivateMessage($idCible,'',$this->md->createEmbed($sqlt));
    }

    public function version($param){
        global $bdd;
        $version = $bdd->query("select value from botExtra where label='version'")->fetch()['value'];
        $this->message->channel->sendMessage("La version du bot est $version");
    }

    public function hook($param){
        global $md;
        $md->createHook($this->message->channel->id);
    }

    public function event($param){
        global $cb;
        $cb->beginEvent();
        $this->message->channel->sendMessage("Tous joueurs avec le rôle event à était ajouter à l'evenement");
    }

    public function degat($param){
        global $cb;
        $param = explode(" ",$param);
        $msg = "";
        if(count($param) != 2){
            $msg ="nécessite deux paramètres";
        }else{
            $result = $cb->degat($param[1],$param[0]);
            if(false===$result){
                $msg="Action impossible car {$param[0]} est hors-combat";
            }elseif($result===0){
                $msg="{$param[0]} est maintenant K.O";
            }elseif($result=="error"){
                $msg="une erreur à était rencontré.";
            }else{
                $msg="Il reste $result points de vie à {$param[0]}";
            }
        }
        $this->message->channel->sendMessage($msg);

    }

    public function mob($param)
    {
        global $bdd;

        $param = explode(" ",$param);
        $msg = "";
        if(count($param) != 2){
            $this->help("mob");return null;
        }

        $qry = "select pv,pm from mob where name='{$param[0]}'";
        $result = $bdd->query($qry)->fetch();
        if(empty($result)){
            $this->retour = "Monstre non connu";return null;
        }

        $qry = "select count(1) as c from combat where name like '{$param[0]}%'";
        if($bdd->query($qry)->fetch()['c']){
            $param[0] .= $bdd->query($qry)->fetch()['c'];
        }

        $tab = [
            'name' => $param[0],
            'pv' => $result['pv'],
            'pm' => $result['pm'],
            'team' => 0,
            'level' => $param[1]
        ];
        $bdd->query(Tools::prepareInsert('combat',$tab));
        $this->retour = "Le monstre ".$param[0]." à bien était rajouté";
    }

    public function topic($param)
    {
        $data = explode(" ",$param);
        $p = [['id'=>'344716194533605376','type'=>1,'allow'=>68608,'deny' => 0]];
        $this->md->createTopic($data[0],$data[1]);
    }
}