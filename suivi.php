<?php

$suivi = $GLOBALS['suivi'][$id];

if(isset($suivi['create'])){
    $etape = count($suivi['create']);

    switch ($etape){
        case 0:
            if($message->content == 0){
                $message->channel->sendMessage("Aller sur cette url : http://51.91.99.243/SDA/index.php?page=new_char");
                unset($GLOBALS['suivi'][$id]['create']);
            }elseif($message->content == 1){
                $message->channel->sendMessage("Si vous désirez mettre en pause la création de votre fiche, utilisez la commande !save.\n\nIndiquez-nous votre prénom.");
                $GLOBALS['suivi'][$id]['create'][] = true;
            }
    }
}