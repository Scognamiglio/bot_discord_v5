<?php

include '/composer/vendor/autoload.php';

spl_autoload_register(function ($class) {
    if(file_exists('lib/' . $class . '.cls.php')){
        include 'lib/' . $class . '.cls.php';
    }else{
        include 'lib/fctForDiscord/'.$class.".php";
    }
});

use Discord\Parts\Embed\Embed;




$isProd = (!empty($_SERVER['argv'][1]) && $_SERVER['argv'][1]=='prod') ? 'prod' : 'local';


// Présent pour éviter de mettre le mot de passe en clair
include "../conf.php";

$allObject = [new fctAdmin(),new fctChara()];
$methodToObject = [];
foreach ($allObject as $i=>$obj){
    $methodToObject = array_merge($methodToObject,array_fill_keys(get_class_methods($obj),$i));
}
// Suppression méthode de structure
unset($methodToObject['__construct']);
unset($methodToObject['__invoke']);
unset($methodToObject['_init']);


static $discord;
static $message;
$md = false;
$cb = new combat();

$_SESSION['continue']=true;