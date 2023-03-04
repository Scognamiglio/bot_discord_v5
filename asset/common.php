<?php

include '/composer/vendor/autoload.php';

foreach (scandir("lib/static") as $cls){
    if(strpos($cls,'.')!==0) {
        include "lib/static/$cls";
    }
}
spl_autoload_register(function ($class) {
    if(file_exists('lib/' . $class . '.cls.php')){
        include 'lib/' . $class . '.cls.php';
    }else{
        include 'lib/fctForDiscord/'.$class.".php";
    }
});

use Discord\Parts\Embed\Embed;





$Env = (!empty($_SERVER['argv'][1]) && $_SERVER['argv'][1]=='prod') ? 'prod' : 'local';


// Présent pour éviter de mettre le mot de passe en clair
include "../conf.php";


$allObject = [];
foreach (scandir("lib/fctForDiscord") as $cls){
    if(strpos($cls,'fct')!==false){
        $cls = substr($cls,0,-4);
        $allObject[] = new $cls();
    }
}
$methodToObject = [];
foreach ($allObject as $i=>$obj){
    $methodToObject = array_merge($methodToObject,array_fill_keys(get_class_methods($obj),$i));
}
// Suppression méthodes appelé en interne (commence par _)
foreach ($methodToObject as $m=>$i){
    if($m[0]=="_"){
        unset($methodToObject[$m]);
    }
}

static $discord;
static $message;
$cb = new combat();

$_SESSION['continue']=true;

// Mets en cache toutes les traductions
trad::init();
