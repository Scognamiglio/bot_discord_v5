<?php
use Discord\Helpers\Deferred;
use function React\Partial\bind as Bind;
class methodDiscord {

    private $discord;
    private $message;

    public function __construct($discord)
    {
        $this->discord = $discord;
        $this->http = $discord->http;
        $this->factory = $discord->getFactory();
    }

    public function isPrivate(){
        return "Discord\Parts\User\Member" != get_class($this->message->author);
    }

    public function isAdmin(){
        return $this->isPrivate() ? $this->message->author->id == '236245509575016451' : $this->verifRole("MJ");
    }

    public function set($label,$value){
        $this->{$label} = $value;
    }

    public function get($label){
        return isset($this->{$label}) ? $this->{$label} : false;
    }

    public function verifRole($name){
        $name = strtolower($name);
        foreach ($this->message->author->roles as $role){
            if($name==strtolower($role['name'])){
                return true;
            }
        }
        return false;
    }

    public function createEmbed($array){
        $embed = new Discord\Parts\Embed\Embed($this->discord);
        foreach ($array as $l=>$v){
            if(!is_array($v)){
                $embed->{"set$l"}($v);
            }
        }
        if(!empty($array['FieldValues'])){
            foreach ($array['FieldValues'] as $f){
                $embed->addFieldValues($f[0],$f[1],$f[2]);
            }
        }
        return $embed;
    }

    public function sendPrivateMessage($id,$text='',$embed=null){
        global $user;
        $this->discord->users->fetch($id)->done(
            function ($user){
                $GLOBALS['user'] = $user;
            },
            function ($error){
            }
        );
        $GLOBALS['user']->sendMessage($text,false,$embed);
        //var_dump($_SESSION['test']);

    }
}