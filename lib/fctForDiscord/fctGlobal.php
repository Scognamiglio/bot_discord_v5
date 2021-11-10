<?php
use Discord\Builders\MessageBuilder;
class fctGlobal extends structure {

    public function __construct()
    {
        $this->required = "";
    }

    function new_char(){
        global $md;
        $msg = "Bienvenue sur le menu pour créer votre fiche !\n\n";
        $msg .= "Deux choix s'ouvre à vous maintenant.```xml\n<site> (conseillé)\nCréer votre fiche en passant par le site, pour ça répondre 0\n\n<Discord>\nCréer votre fiche en passant par discord, pour ça répondre 1\n```";

        //$GLOBALS['suivi'][$this->id]['create'] = [];

        $func = function ($interaction, $options) use (&$func) {
            global $md;
            foreach ($options as $option) {
                $steps = [
                    [
                        'msg' => 'Quel est ton genre ?',
                        'param' => [['Homme','1-Homme'],['Femme','1-Femme'],['Autre','1-Autre']],
                        'bdd' => 'x',
                    ],
                    [
                        'msg' => 'Quel est ta race ?',
                        'param' => [['humain','2-humain','faible'],['vampire','2-vampire','fort'],['retour','0-x','Changer le genre']]
                    ]
                ];
                $selected = $option->getValue();
                $label = $option->getLabel();
                if($label == "Site"){
                    $interaction->updateMessage(MessageBuilder::new()->setContent("Aller sur cette url : http://51.91.99.243/SDA/index.php?page=new_char"));
                    continue;
                }
                $step = explode("-",$selected)[0];
                if(!empty($steps[$step])){
                    $m = $md->createSelect($steps[$step]['msg'],$steps[$step]['param'],$func);
                }else{
                    $m = MessageBuilder::new()->setContent("Ton choix est : $label");
                }
                $interaction->updateMessage($m);
            }
        };
        $md->createSelect($msg,[['Site','0-Site'],['Discord','0-Discord']],$func);
    }

}