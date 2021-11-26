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
        $msg .= "Deux choix s'ouvre à vous maintenant.```xml\n<site> (conseillé PC)\nCréer votre fiche en passant par le site\n\n<Discord>(conseillé phone)\nCréer votre fiche en passant par discord\n```";


        $func = function ($interaction, $options) use (&$func) {
            global $md,$bdd;

            $getJsonBdd = function ($qry){
                global $bdd;
                return json_decode($bdd->query($qry)->fetch()[0],true);
            };
            $idUserInter = $interaction->member->user->id;
            $msgDefault = "Bienvenue sur le menu pour créer votre fiche !\n\n";
            $msgDefault .= "Deux choix s'ouvre à vous maintenant.```xml\n<site> (conseillé PC)\nCréer votre fiche en passant par le site\n\n<Discord>(conseillé phone)\nCréer votre fiche en passant par discord\n```";

            $msgError = "> ***__Seul le créateur l'interaction doit cliquer.__***\n\n";
            $steps = [
                -1 => [
                    'msg' => $msgDefault,
                    'param' => 'newChar',
                    'bddBefore' => 'x'
                ],
                0 => [
                    'msg' => 'Quel est ton genre ?',
                    'param' => 'genre',
                    'bddBefore' => 'x',
                ],
                1 => [
                    'msg' => 'Quel est ta voie primaire ? #voie',
                    'param' => 'vPrimaire',
                    'bddBefore' => 'genre'
                ],
                2 => [
                    'msg' => 'Quel est ta voie secondaire ? #voie',
                    'param' => 'vPrimaire',
                    'bddBefore' => 'vPrimaire'
                ],
                3 => [
                    'msg' => 'Quel est ta race ? #race',
                    'param' => 'race',
                    'bddBefore' => 'vSecondaire',
                ]
            ];

            $selected = $options[0]->getValue();
            $label = $options[0]->getLabel();
            if($label == "Site"){
                return $interaction->updateMessage(MessageBuilder::new()->setContent("Aller sur cette url : http://51.91.99.243/SDA/index.php?page=new_char"));
            }
            $arrayData = explode("-",$selected);
            $step = $arrayData[0];
            $id = $arrayData[1];
            $error = $id != $idUserInter;
            if($error && $label!="Retour") {
                $step--;
            }elseif($steps[$step]['bddBefore'] != 'x'){
                $bdd->query("insert into ficheData values('$id','{$steps[$step]['bddBefore']}','{$arrayData[2]}',now()) ON DUPLICATE KEY UPDATE value='{$arrayData[2]}',dateInsert=now()");

            }

            if(empty($steps[$step]) && !$error){
                return $interaction->updateMessage(MessageBuilder::new()->setContent("Ton choix est : $label"));
            }
            $msg = ($error ? $msgError : "").$steps[$step]['msg'];

            $json = $getJsonBdd("select value from botExtra where label='{$steps[$step]['param']}'");
            $dataTab = array_keys($json);
            $out = [];
            foreach ($dataTab as $d){
                $out[] = [$d,($step+1)."-$id-$d"];
            }
            if($step!=-1){$out[] = ["Retour","$step-$id-Retour"];}

            $interaction->updateMessage($md->createSelect($msg,$out,$func));
        };
        $md->createSelect($msg,[['Site',"0-{$this->id}-Site"],['Discord',"0-{$this->id}-Discord"]],$func);
    }

}