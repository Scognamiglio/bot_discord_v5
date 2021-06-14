<?php
class fctAdmin extends structure {

    public function repeat($param){
        $this->message->channel->sendMessage($param);
    }

    public function stop($param){
        $_SESSION['continue']=false;
        $this->message->channel->sendMessage("Bonne nuit <3");
        $this->md->get('discord')->close();
    }

    public function send($param){
        preg_match_all("/([^ ]*) {([^}]*)}(.*)/s",$param,$array);
        $idCible = $array[1][0];
        $title = $array[2][0];
        $newMsg = $array[3][0];

        $chara = $this->bdd->query("select * from perso p INNER JOIN persoClasse pc ON p.idPerso=pc.idPerso where p.idPerso='{$this->id}'")->fetch();
        $sqlt = [
            'Author' => $chara['prenom'],
            'Thumbnail' => $chara['avatar'],
            'Title' => $title,
            "Description" => $newMsg,
            "Color" => "0x00AE86"
        ];
        //$message->channel->sendEmbed($md->createEmbed($sqlt));
        $this->md->sendPrivateMessage($idCible,'',$this->md->createEmbed($sqlt));
    }

    public function version($param){
        $version = $this->bdd->query("select value from botExtra where label='version'")->fetch()['value'];
        $this->message->channel->sendMessage("La version du bot est $version");
    }
}