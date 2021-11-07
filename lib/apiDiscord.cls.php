<?php
class ApiDiscord
{
    private static $urlApi = "https://discord.com/api/v9/";
    public static function hello(){
        return "b";
    }

    public static function postDiscord($url,$post){
        $headers = [
            'Content-Type: application/json; charset=utf-8',
            'authorization:Bot '.$GLOBALS['token'][$GLOBALS['Env']],
            'User-Agent:DiscordBot (https://github.com/discord-php/DiscordPHP, v5.1.1)'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::$urlApi.$url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post));

        $response   = curl_exec($ch);
        return json_decode($response,true);
    }

    public static function createHook($idChannel){
        global $bdd;
        $token = self::postDiscord("channels/$idChannel/webhooks",['name' => 'Hook of Astrem']);
        $bdd->query("insert into hook values('$idChannel','{$token['token']}','{$token['id']}') ON DUPLICATE KEY UPDATE token='{$token['token']}',idHook='{$token['id']}'");
    }

    public static function speakHook($name,$img,$msg){
        global $bdd,$message;
        $id = $message->channel->id;
        $qry = "select token,idHook from hook where idCanal='$id'";
        $result = $bdd->query($qry)->fetchAll();
        if(count($result) > 0){
            $POST = ['username' => $name, 'content' => $msg, 'avatar_url' => $img];
            ApiDiscord::postDiscord("webhooks/{$result[0]['idHook']}/{$result[0]['token']}",$POST);
            return true;
        }
        return false;

    }

    public static function createTopic($name,$type,$parentid=null,$permision=null){
        global $message;
        $body = [
            'name' => $name,
            'type'=>$type
        ];

        if($type==0){$body['parent_id']=empty($parentid) ? $message->channel->parent_id : $parentid;}
        $body['permission_overwrites']=$permision;

        return ApiDiscord::postDiscord("/guilds/".$message->channel->guild->id."/channels",$body);
    }
}