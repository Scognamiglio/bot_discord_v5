<?php
/* rapport direct a Discord */
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

    public function createTopic($name,$type,$permision=null){
        $body = [
            'name' => $name,
            'type'=>$type
        ];
        if($type==0){$body['parent_id']=$this->message->channel->parent_id;}
        $body['permission_overwrites']=$permision;

        $this->postDiscord("https://discord.com/api/v9/guilds/".$this->message->channel->guild->id."/channels",$body);
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

    public function postDiscord($url,$post){
        $headers = [
            'Content-Type: application/json; charset=utf-8',
            'authorization:Bot '.$GLOBALS['token'][$GLOBALS['Env']],
            'User-Agent:DiscordBot (https://github.com/discord-php/DiscordPHP, v5.1.1)'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post));

        $response   = curl_exec($ch);
        return json_decode($response,true);
    }

    public function createHook($idChannel){
        global $bdd;
        $token = $this->postDiscord("https://discord.com/api/v9/channels/$idChannel/webhooks",['name' => 'Hook of Astrem']);
        $bdd->query("insert into hook values('$idChannel','{$token['token']}','{$token['id']}') ON DUPLICATE KEY UPDATE token='{$token['token']}',idHook='{$token['id']}'");

    }

    public function speakHook($name,$img,$msg){
        global $bdd;
        $id = $this->message->channel->id;
        $qry = "select token,idHook from hook where idCanal='$id'";
        $result = $bdd->query($qry)->fetchAll();
        if(count($result) > 0){
            $POST = ['username' => $name, 'content' => $msg, 'avatar_url' => $img];
            $this->postDiscord("https://discord.com/api/webhooks/{$result[0]['idHook']}/{$result[0]['token']}",$POST);
            return true;
        }
        return false;

    }
}