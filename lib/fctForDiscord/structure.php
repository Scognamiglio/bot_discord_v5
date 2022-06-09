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
        $isParamLinux = false;
        foreach ($struct as $check){
            if(strpos(strtolower($data),"-".$check)!==false){
                $isParamLinux = true;
                break;
            }
        }

        if($isParamLinux){
            $regex = "/-(".implode('|',$struct).') ?((?:(?! -'.implode('| -',$struct).').)*)/is';
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
        global $md;
        $this->_init();
        $error = "";
        switch ($this->required) {
            case "fiche":
                if(empty(sql::fetch("select 1 from perso where idPerso='{$this->id}'")))
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
            return $this->help($argument[0]);
        }
        return $this->{$argument[0]}($argument[1]);
    }

    public function help($param){

        if(empty($param)){
            $qry = "select idHelp from help";
            $result = sql::fetchAll($qry);
            $msg = "fonction avec une aide connu : ";
            foreach ($result as $r){
                $msg.="\n".$r['idHelp'];
            }
            return $msg;
        }

        $qry = "select author,texte from help where idHelp='$param'";
        $result = sql::fetch($qry);

        if(!empty($result)){
            $embed['Author'] = "!$param\n";
            $embed['Title'] = "Créateur : ".$result['author'];
            $embed['Description'] = $this->_cleanHelp($result['texte']);
            $embed['Color'] = "0x4BFFEF";
            return $embed;
        }
        return "Aucune aide écrite pour la commande.";
    }

    public function _cleanHelp($descr){
        $array = explode("\n",$descr);
        foreach ($array as $i=>$str){
            $array[$i] = trim($str);
        }
        return implode("\n",$array);
    }

}