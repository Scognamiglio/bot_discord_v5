<?php

class structure {

    public function _init(){
        $this->md = $GLOBALS['md'];
        $this->message = $this->md->get('message');
        $this->id = $this->message['author']['id'];
        $GLOBALS['id'] = $this->message['author']['id'];
        $this->isPrivate = "Discord\Parts\User\Member" != get_class($this->message->author);
        $this->isAdmin = $this->isPrivate ? $this->message->author->id == '236245509575016451' : $this->md->verifRole("MJ");
    }

    public function _TraitementData($data,$struct){
        $retour = [];
        if(strpos($data,"-".$struct[0])!==false){
            $regex = "/-(".implode('|',$struct).') ((?:(?! -'.implode('| -',$struct).').)*)/s';
            preg_match_all($regex,$data,$array);
            foreach ($array[0] as $i=>$t)
                $retour[$array[1][$i]] = $array[2][$i];
        }else{
            $regex = '/\"([^"]*)\"/';
            preg_match_all($regex,$data,$array);
            foreach ($array[1] as $i=>$t)
                $retour[$struct[$i]] = $t;

        }
        return $retour;
    }

    public function __invoke($argument)
    {
        global $bdd,$md;
        $this->_init();
        $error = "";
        switch ($this->required) {
            case "fiche":
                if(empty($bdd->query("select 1 from perso where idPerso='{$this->id}'")->fetch()))
                    $error = "Commande nécéssitant une fiche";
                break;
            case "admin":
                if(!$md->isAdmin())
                    $error = "Commande nécéssitant d'être admin";
                break;
        }
        if(!empty($error)){
            $this->message->channel->sendMessage($error);
            return null;
        }
        $this->{$argument[0]}($argument[1]);
    }



}