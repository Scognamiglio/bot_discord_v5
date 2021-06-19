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

        if($argument[1]=="help"){
            $this->help($argument[0]);
            return null;
        }
        $this->{$argument[0]}($argument[1]);
    }

    public function help($param){
        global $md;

        $help = [
            'pnj' => [
                'Title' => "Zheneos Hikari",
                "Description" =>
                    "> __**Permet la création d'un pnj**__
                    
                    ```
                    !pnj -alias m -nom my name -image url
                    !pnj \"m\" \"my name\" \"url\"
                    ```    
                    **alias** : à mettre entre () au début du message
                    **nom** : Le nom du pnj
                    **image** : L'avatar du pnj"
            ],
            'fiche' => [
                'Title' => "Zheneos Hikari",
                "Description" => "Affiche la fiche du personnage."
            ]
        ];

        if(!empty($help[$param])){
            $embed = $help[$param];
            $embed['Author'] = "!$param\n";
            $embed['Title'] = "Créateur : ".$embed['Title'];
            $embed['Description'] = $this->_cleanHelp($embed['Description']);
            $embed['Color'] = "0x4BFFEF";
            $this->message->channel->sendEmbed($md->createEmbed($embed));
        }else{
            if(empty($param)){
                $msg = implode("\n",array_keys($help));
            }else{
                $msg = "Aucune aide écrite pour la commande.";
            }

            $this->message->channel->sendMessage($msg);
        }
    }

    public function _cleanHelp($descr){
        $array = explode("\n",$descr);
        foreach ($array as $i=>$str){
            $array[$i] = trim($str);
        }
        return implode("\n",$array);
    }

}