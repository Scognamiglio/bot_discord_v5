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


static $discord;
static $message;
$md = false;
$cb = new combat();
$fctDiscord = new fctDiscord();

$_SESSION['continue']=true;