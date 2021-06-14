<?php

class structure {
    function __construct(){
        $this->bdd = $GLOBALS['bdd'];
    }

    public function _init(){
        $this->md = $GLOBALS['md'];
        $this->message = $this->md->get('message');
        $this->id = $this->message['author']['id'];
        $GLOBALS['id'] = $this->message['author']['id'];
        $this->isPrivate = "Discord\Parts\User\Member" != get_class($this->message->author);
        $this->isAdmin = $this->isPrivate ? $this->message->author->id == '236245509575016451' : $this->md->verifRole("MJ");
    }

    public function __invoke($argument)
    {
        $this->_init();
        $this->{$argument[0]}($argument[1]);
    }



}