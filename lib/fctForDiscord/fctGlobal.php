<?php
class fctGlobal extends structure {

    public function __construct()
    {
        $this->required = "";
    }

    function new_char(){
        global $md;
        // Rajouté une vérification sur l'existance du personnage.
        $embed['Author'] = "Création de fiche";
        $embed['Title'] = "Bienvenue sur le menu pour créer votre fiche !";
        $embed['Description'] = "Deux choix s'ouvre à vous maintenant.```xml\n<site> (conseillé)\nCréer votre fiche en passant par le site, pour ça répondre 0\n\n<Discord>\nCréer votre fiche en passant par discord, pour ça répondre 1\n```";
        $embed['Color'] = "0x4BFFEF";
        $GLOBALS['suivi'][$this->id]['create'] = [];
        $this->message->channel->sendEmbed($md->createEmbed($embed));
    }

}