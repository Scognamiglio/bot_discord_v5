<?php
// Créateur Scognamiglio Loïc.
include "asset/common.php";

use Discord\Discord;
use Discord\WebSockets\Intents;
$discord = new Discord([
    'token' => $token[$Env],
    'loadAllMembers' => true,
    'intents' => Intents::getDefaultIntents() | Intents::GUILD_MEMBERS,
    'logger' => new \Psr\Log\NullLogger()
]);


$discord->on('ready', function ($discord) {
    global $md;
    $md = new methodDiscord($discord);

    echo "Bot is ready!", PHP_EOL;


    $discord->on('CHANNEL_CREATE',function ($channel, Discord $discord) {
        global $md;
        if(!$channel->is_private && $channel->type==0){
            ApiDiscord::createHook($channel->id);
        }
    });

    // Listen for messages.
    $discord->on('message', function ($message, $discord) {
        global $md;
        $md->set("message",$message);
        $GLOBALS['message'] = $message;
       if(!$md->isBot()){
           $id = $message->author->id;
           if($message->content[0] == '!'){
               global $methodToObject,$allObject;
               preg_match_all("/!([^ ]*) ?(.*)?/s",$message->content,$array);

               $act = strtolower($array[1][0]);

               if(isset($methodToObject[$act])){
                   // Check
                   $retour = $allObject[$methodToObject[$act]]([$act,$array[2][0]]);
                   if(!empty($retour)){
                       if(is_array($retour)){
                           $md->sendEmbed($retour);
                       }else{
                           $message->channel->sendMessage($retour);
                       }
                   }
               }

           }
           elseif(preg_match_all("/^\(([^)]*)\) (.*)$/s",$message->content,$array) > 0){
               $qry = "SELECT name,img FROM pnj WHERE alias='{$array[1][0]}' AND who='$id'";
               $result = sql::fetchAll($qry);
               if(count($result) > 0){
                   if(ApiDiscord::speakHook($result[0]['name'],$result[0]['img'],$array[2][0])){
                       $message->delete();
                   }
               }
           }
           elseif(isset($GLOBALS['suivi'][$id]['create'])){
                include "suivi.php";
           }
       }


       // Vérifie la présence d'action prévu.
       if(!isset($GLOBALS['t']) || (time()-$GLOBALS['t']) > 1 /*5*/){
           $GLOBALS['t'] = time();
           $execs = sql::fetchAll("SELECT action,param,id FROM exec WHERE horodate < NOW() AND isExec=0");
           if(count($execs) > 0){
               new exec($execs);
           }
       }
    });
});
$discord->run();

if($_SESSION['continue']){
    echo "newRun";
}