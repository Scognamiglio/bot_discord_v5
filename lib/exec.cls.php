<?php

class exec {

    function __construct($instructions)
    {
        foreach ($instructions as $i => $e){
            if(method_exists ($this,$e['action'])){
                $this->{$e['action']}($e['param'],$e['id']);
            }else{
                $GLOBALS['md']->sendPrivateMessage('236245509575016451',"Utilisation d'une action[{$e['action']}] inconnu avec l'id {$e['id']}");
                $this->setIsExec($e['id']);
            }
        }
    }

    public function message($json,$id){
        global $md;
        $json = json_decode($json,true);
        $md->sendPrivateMessage($json['cible'],$json['message']);
        $this->setIsExec($id);
    }

    public function setIsExec($id){
        sql::query("update exec set isExec=1 where id='$id'");
    }
}