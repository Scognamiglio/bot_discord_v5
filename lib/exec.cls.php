<?php

class exec {

    function __construct($instructions)
    {
        foreach ($instructions as $i => $e){
            if(method_exists ($this,$e['action'])){
                $this->{$e['action']}($e['param'],$e['id']);
            }else{
                ApiDiscord::sendPrivateMessage("Utilisation d'une action[{$e['action']}] inconnu avec l'id {$e['id']}",'236245509575016451'); // @TODO à check
                $this->setIsExec($e['id']);
            }
        }
    }

    public function message($json,$id){
        $json = json_decode($json,true);
        ApiDiscord::sendPrivateMessage($json['message'],$json['cible']); // @TODO à check
        $this->setIsExec($id);
    }

    public function setIsExec($id){
        sql::query("update exec set isExec=1 where id='$id'");
    }
}