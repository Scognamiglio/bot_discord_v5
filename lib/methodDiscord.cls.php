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


    public function getMemberInGuild(){
        if($this->isPrivate()){
            return false;
        }

        return $this->message->channel->guild->members;
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

    public function createHook($idChannel){
        global $bdd;
        $url = "https://discord.com/api/v9/channels/$idChannel/webhooks";
        $headers = [
            'Content-Type: application/json; charset=utf-8',
            'authorization:Bot '.$GLOBALS['token'][$GLOBALS['isProd']],
            'User-Agent:DiscordBot (https://github.com/discord-php/DiscordPHP, v5.1.1)'];
        $POST = ['name' => 'Hook of Astrem'];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($POST));
        $response   = curl_exec($ch);
        $token = json_decode($response,true);
        $bdd->query("insert into hook values('$idChannel','{$token['token']}','{$token['id']}') ON DUPLICATE KEY UPDATE token='{$token['token']}',idHook='{$token['id']}'");

    }

    public function speakHook($name,$img,$msg){
        global $bdd;
        $id = $this->message->channel->id;
        $qry = "select token,idHook from hook where idCanal='$id'";

        $result = $bdd->query($qry)->fetchAll();
        if(count($result) > 0){
            $url = "https://discord.com/api/webhooks/{$result[0]['idHook']}/{$result[0]['token']}";
            $headers = [ 'Content-Type: application/json; charset=utf-8' ];
            $POST = [ 'username' => $name, 'content' => $msg, 'avatar_url' => $img];
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($POST));
            $response   = curl_exec($ch);
            echo $response;
            return true;
        }else{
            return false;
        }
    }
}