<?php

class structure {

    public $required;
    public function _init(){
        $this->delete = false;
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
        $this->_init();
        $error = "";
        switch ($this->required) {
            case "fiche":
                if(empty(sql::fetch("select 1 from perso where idPerso='{$this->id}'")))
                    $error = _t("global.notWithFiche");
                break;
            case "admin":
                if(!$this->isAdmin)
                    $error = _t("global.notAdmin");
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
            
            //repetition des lignes présente dans tools dans alias a généraliser : dans tools ? 
            global $tab;
            if (empty($tab)) {
                foreach (sql::fetchAll("SELECT * from alias") as $ligne ) {
                    $tab[$ligne["original"]] = array_map("trim",explode(",",$ligne["autres"])) ;
                }            
            } 

            $result = sql::fetchAll( "SELECT idHelp from help");
            $msg= _t("help.listHead");
            foreach ($result as $r){
                $msg.="\n".$r['idHelp']." (".((!empty($tab[$r['idHelp']])) ? implode(", ",$tab[$r['idHelp']]) : _t("global.withoutAlias")).")";
            }
            return $msg;
        }

        $qry = "SELECT author,texte from help where idHelp='$param'";
        $result = sql::fetch($qry);

        if(!empty($result)){
            $embed['Author'] = "!$param\n";
            $embed['Title'] = "Créateur : ".$result['author'];
            $embed['Description'] = $this->_cleanHelp($result['texte']);
            $embed['Color'] = "0x4BFFEF";
            return $embed;
        }
        return (_t("help.notExistingHelp"));
    }

    public function _cleanHelp($descr){
        $array = explode("\n",$descr);
        foreach ($array as $i=>$str){
            $array[$i] = trim($str);
        }
        return implode("\n",$array);
    }

}