<?php
/* rapport direct a Discord */
use Discord\Helpers\Deferred;
use function React\Partial\bind as Bind;
use Discord\Builders\MessageBuilder;
use Discord\Builders\Components\SelectMenu;
use Discord\Builders\Components\Option;
class methodDiscord {

    private $discord,$http,$message,$factory ;


    /*
     * Construct
     */
    public function __construct($discord)
    {
        $this->discord = $discord;
        $this->http = $discord->http;
        $this->factory = $discord->getFactory();
    }

    /*
     * Accesseur
     */
    public function set($label,$value){
        $this->{$label} = $value;
    }

    public function get($label){
        return isset($this->{$label}) ? $this->{$label} : false;
    }



    /*
     * Etat
     */
    public function isPrivate(){
        return "Discord\Parts\User\Member" != get_class($this->message->author);
    }

    public function isAdmin(){
        return $this->isPrivate() ? $this->message->author->id == '236245509575016451' : $this->verifRole("MJ");
    }

    public function isBot(){
        return $this->isPrivate() ? $this->message['author']['bot'] : $this->message['author']['user']['bot'];
    }

    /*
     * Rôles
     */
    public function verifRole($name){
        $name = strtolower($name);
        foreach ($this->message->author->roles as $role){
            if($name==strtolower($role['name'])){
                return true;
            }
        }
        return false;
    }

    public function getRoleId($nameRole){
        if($this->isPrivate()){
            return false;
        }

        foreach ($this->message->channel->guild->roles as $id=>$role){
            if($role->name == $nameRole){
                return $id;
            }
        }
    }
    public function getUserWithRole($role){
        if($this->isPrivate()){
            return false;
        }
        $idRole = $this->getRoleId($role);
        $members = $this->getMemberInGuild();
        $return = [];
        foreach ($members as $member){
            if(!empty($member->roles[$idRole])){
                $return[] = $member;
            }
        }
        return $return;
    }

    public function getUserbyId($idRole){
        if($this->isPrivate()){
            return false;
        }
        $members = $this->getMemberInGuild();
        $return = [];
        foreach ($members as $member){
            if($member->id == $idRole){
                return $member;
            }
        }
        return false;
    }

    public function getChannelById($idChannel){
        $allChannel = $this->message->channel->guild->channels;
        return empty($allChannel[$idChannel]) ? null : $allChannel[$idChannel];
    }


    public function getMemberInGuild(){
        if($this->isPrivate()){
            return false;
        }

        return $this->message->channel->guild->members;
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

    public function sendEmbed($array){
        $this->message->channel->sendEmbed($this->createEmbed($array));
    }

    public function sendPrivateMessage($id,$text='',$tabEmbed=null){
        global $user;
        $this->discord->users->fetch($id)->done(
            function ($user){
                $GLOBALS['user'] = $user;
            },
            function ($error){
            }
        );
        $embed = (empty($tabEmbed) ? null : $this->createEmbed($tabEmbed));
        $GLOBALS['user']->sendMessage($text,false,$embed);

    }

    public function createSelect($msg,$options,$func,$return = false){
        $message = $this->message;
        $discord = $this->discord;

        $m = MessageBuilder::new();
        if(is_array($msg)){
            $m->addEmbed($this->createEmbed($msg));
        }else{
            $m->setContent($msg);
        }
        $select = SelectMenu::new();
        foreach ($options as $array){
            $o = Option::New($array[0],(empty($array[1]) ? $array[0] : $array[1]));
            if(!empty($array[2])){
                $o->setDescription($array[2]);
            }
            $select->addOption($o);
        }


        $m->addComponent($select);
        if(!$return){
            $this->message->channel->sendMessage($m)->then(function($new_message) use ($discord, $message){
                $message->delete(); //Delete the original ;suggestion message
            });
        }

        $select->setListener($func, $discord);
        return $m;
    }
}