<?php
// Créateur Scognamiglio Loïc.
include "src/common.php";

use Discord\Discord;
$discord = new Discord([
    'token' => $token[$isProd],
    'loadAllMembers' => true
]);


$discord->on('ready', function ($discord) {
    global $md;
    $md = new methodDiscord($discord);

    echo "Bot is ready!", PHP_EOL;


    $discord->on('CHANNEL_CREATE',function ($channel, Discord $discord) {
        global $md;
        if(!$channel->is_private && $channel->type==0){
            $md->createHook($channel->id);
        }
    });

    // Listen for messages.
    $discord->on('message', function ($message, $discord) {
        global $bdd,$md;
        $md->set("message",$message);
       if(!$message['author']['user']['bot'] && !$message['author']['bot']){
           $id = $message->author->id;
           if($message->content[0] == '!'){
               global $methodToObject,$allObject;
               preg_match_all("/!([^ ]*) ?(.*)?/s",$message->content,$array);

               $act = strtolower($array[1][0]);

               if(isset($methodToObject[$act])){
                   $allObject[$methodToObject[$act]]([$act,$array[2][0]]);
                   $retour = $allObject[$methodToObject[$act]]->retour;
                   if(!empty($retour)){
                       $message->channel->sendMessage($retour);
                   }
               }

           }
           elseif(preg_match_all("/^\(([^)]*)\) (.*)$/s",$message->content,$array) > 0){
               $qry = "SELECT name,img FROM pnj WHERE alias='{$array[1][0]}' AND who='$id'";
               $result = $bdd->query($qry)->fetchAll();
               if(count($result) > 0){
                   if($md->speakHook($result[0]['name'],$result[0]['img'],$array[2][0])){
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
           $execs = $bdd->query("SELECT action,param,id FROM exec WHERE horodate < NOW() AND isExec=0")->fetchAll();
           if(count($execs) > 0){
               new exec($execs);
           }
       }
    });
});

while($_SESSION['continue']){
    $discord->run();
}