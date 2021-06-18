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

    // Listen for messages.
    $discord->on('message', function ($message, $discord) {
        $GLOBALS['md']->set("message",$message);
       if(!$message['author']['user']['bot'] && !$message['author']['bot']){
           if($message->content[0] == '!'){
               global $methodToObject,$allObject;
               preg_match_all("/!([^ ]*) ?(.*)?/s",$message->content,$array);

               $act = strtolower($array[1][0]);

               if(isset($methodToObject[$act])){
                   $idObject = $methodToObject[$act];
                   if($idObject==0 && !$GLOBALS['md']->isAdmin()){
                       $message->channel->sendMessage("interdit !");
                   }else{
                       $allObject[$idObject]([$act,$array[2][0]]);
                   }
               }

           }
       }

       // Vérifie la présence d'action prévu.
       if(!isset($GLOBALS['t']) || (time()-$GLOBALS['t']) > 1 /*5*/){
           global $bdd;
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