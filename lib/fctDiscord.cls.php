<?php

class fctDiscord {

    function init(){
        $this->md = $GLOBALS['md'];
        $this->message = $this->md->get('message');
        $this->id = $this->message['author']['id'];
        $this->isPrivate = "Discord\Parts\User\Member" != get_class($this->message->author);
        $this->isAdmin = $this->isPrivate ? $this->message->author->id == '236245509575016451' : $this->md->verifRole("MJ");
    }

    public function appel($act,$param){
        global $methodToObject,$allObject;
        $this->init();
        $act = strtolower($act);

        if(isset($methodToObject[$act])){
            $idObject = $methodToObject[$act];
            if($idObject==0 && !$this->isAdmin){
                $this->message->channel->sendMessage("interdit !");
            }else{
                $allObject[$idObject]([$act,$param]);
            }
        }

    }





}