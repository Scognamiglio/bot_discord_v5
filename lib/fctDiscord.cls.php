<?php

class fctDiscord {
    function __construct(){
        $this->allObject = [new fctAdmin(),new fctChara];
        $this->methodToObject = [];
        foreach ($this->allObject as $i=>$obj){
            $this->methodToObject = array_merge($this->methodToObject,array_fill_keys(get_class_methods($obj),$i));
        }
        // Suppression méthode de structure
        unset($this->methodToObject['__construct']);
        unset($this->methodToObject['__invoke']);
        unset($this->methodToObject['_init']);
    }

    function init(){
        $this->md = $GLOBALS['md'];
        $this->message = $this->md->get('message');
        $this->id = $this->message['author']['id'];
        $this->isPrivate = "Discord\Parts\User\Member" != get_class($this->message->author);
        $this->isAdmin = $this->isPrivate ? $this->message->author->id == '236245509575016451' : $this->md->verifRole("MJ");
    }

    public function appel($act,$param){
        $this->init();
        $act = strtolower($act);

        if(isset($this->methodToObject[$act])){
            $idObject = $this->methodToObject[$act];
            if($idObject==0 && !$this->isAdmin){
                $this->message->channel->sendMessage("interdit !");
            }else{
                $this->allObject[$idObject]([$act,$param]);
            }
        }

    }





}