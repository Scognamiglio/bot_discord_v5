<?php
class ApiDiscord
{
    private static $urlApi = "https://discord.com/api/v9/";

    public static function sendDiscord($method,$url,$post){
        $type = [
            'post' => HTTP_Request2::METHOD_POST,
            'put' => HTTP_Request2::METHOD_PUT,
            'delete' => HTTP_REQUEST2::METHOD_DELETE,
            'patch' => 'PATCH',
            'get' => 'GET',
        ];
        $methodUse = $type[strtolower($method)];
        if(empty($methodUse)) {return false;}

        $request = new HTTP_Request2();
        $request->setUrl(self::$urlApi.$url);
        $request->setMethod($methodUse);
        $request->setConfig(array(
            'follow_redirects' => TRUE
        ));
        $request->setHeader(array(
            'content-type' => 'application/json',
            'authorization' => 'Bot '.$GLOBALS['token'][$GLOBALS['Env']],
        ));
        if(!empty($post)){$request->setBody(json_encode($post));}
        $response = $request->send();
        return json_decode($response->getBody(),true);

    }

    public static function createHook($idChannel){
        $token = self::sendDiscord('post',"channels/$idChannel/webhooks",['name' => 'Hook of Astrem']);
        sql::query("insert into hook values('$idChannel','{$token['token']}','{$token['id']}') ON DUPLICATE KEY UPDATE token='{$token['token']}',idHook='{$token['id']}'");
    }

    public static function speakHook($name,$img,$msg){
        global $message;
        $id = $message->channel->id;
        $qry = "select token,idHook from hook where idCanal='$id'";
        $result = sql::fetchAll($qry);
        if(count($result) > 0){
            $POST = ['username' => $name, 'content' => $msg, 'avatar_url' => $img];
            ApiDiscord::sendDiscord('post',"webhooks/{$result[0]['idHook']}/{$result[0]['token']}",$POST);
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

        return ApiDiscord::sendDiscord('post',"guilds/".$message->channel->guild->id."/channels",$body);
    }

    public static function ChangePerm($pId,$permSet){
        return ApiDiscord::sendDiscord("put","channels/$pId/permissions/{$permSet['id']}",$permSet);
    }

    public static function sendMessage($msg,$idChannel=null){
        global $message;
        $idChannel = $idChannel ?? $message->channel->id;
        return ApiDiscord::sendDiscord("post","channels/$idChannel/messages",['content'=>$msg]);
    }

    public static function editMessage($idChannel,$idMessage,$msg){
        return ApiDiscord::sendDiscord("patch","channels/$idChannel/messages/$idMessage",['content'=>$msg]);
    }

    public static function deleteMessage($idChannel=null,$idMessage=null){
        global $message;
        $idChannel = $idChannel ?? $message->channel->id;
        $idMessage = $idMessage ?? $message->id;
        return ApiDiscord::sendDiscord("delete","channels/$idChannel/messages/$idMessage",[]);
    }

    //TODO Gestion Embed
    public static function getMessage($idChannel=null,$idMessage=null){
        global $message;
        $idChannel = $idChannel ?? $message->channel->id;
        if(empty($idMessage)){
            $tabMessage = [];
            $jsonS = ApiDiscord::sendDiscord("get","channels/$idChannel/messages",[]);
            foreach ($jsonS as $json){
                $tabMessage[$json['id']] = $json['content'];
            }
            return $tabMessage;
        }
        $json = ApiDiscord::sendDiscord("get","channels/$idChannel/messages/$idMessage",[]);
        return [$json['id'] => $json['content']];
    }
}