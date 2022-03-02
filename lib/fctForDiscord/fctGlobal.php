<?php
use Discord\Builders\MessageBuilder;
class fctGlobal extends structure {

    public function __construct()
    {
        $this->required = "";
    }

    function new_char(){
        global $md;
        if(!empty(sql::fetch("select 1 from perso where idPerso='{$this->id}'"))){
            return "Tu as déjà une fiche.";
        }
        $msg = "Bienvenue sur le menu pour créer votre fiche !\n\n";
        $msg .= "Deux choix s'ouvre à vous maintenant.```xml\n<site> (conseillé PC)\nCréer votre fiche en passant par le site\n\n<Discord>(conseillé phone)\nCréer votre fiche en passant par discord\n```";


        $func = function ($interaction, $options) use (&$func) {
            global $md;

            $idUserInter = $interaction->user->id;
            $msgDefault = "Bienvenue sur le menu pour créer votre fiche !\n\n";
            $msgDefault .= "Deux choix s'ouvre à vous maintenant.```xml\n<site> (conseillé PC)\nCréer votre fiche en passant par le site\n\n<Discord>(conseillé phone)\nCréer votre fiche en passant par discord\n```";

            $msgError = "> ***__Seul le créateur l'interaction doit cliquer.__***\n\n";
            $steps = [
                -1 => [
                    'msg' => $msgDefault,
                    'param' => 'newChar',
                ],
                0 => [
                    'msg' => 'Quel est ton genre ?',
                    'param' => 'genre',
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
            if($error) {
                $step = $step + ($label == "Retour" ? 1 : -1);
            }elseif(!empty($steps[$step]['bddBefore']) && $label!="Retour"){
                sql::query("insert into ficheData values('$id','{$steps[$step]['bddBefore']}','{$arrayData[2]}',now()) ON DUPLICATE KEY UPDATE value='{$arrayData[2]}',dateInsert=now()");
            }
            if(empty($steps[$step]) && !$error){
                $arrayData[2] = ucfirst($arrayData[2]);
                sql::query("insert into ficheData values('$id','race','{$arrayData[2]}',now()) ON DUPLICATE KEY UPDATE value='{$arrayData[2]}',dateInsert=now()");
                return $interaction->updateMessage(MessageBuilder::new()->setContent("Utiliser la commande !dataFiche pour finir de compléter ta fiche"));
            }
            $msg = ($error ? $msgError : "").$steps[$step]['msg'];

            if($steps[$step]['param']=="race"){
                $raceByVoie = sql::getJsonBdd("select value from botExtra where label='raceByVoie'");
                $array = ['all'];
                $dataTab = [];
                $result = sql::fetchAll("SELECT value FROM ficheData WHERE idPerso='$id' AND label IN ('vPrimaire','vSecondaire')");
                foreach ($result as $v){
                    $array[] = $v[0];
                }
                foreach ($array as $v) {
                    $v = strtolower($v);
                    if (!empty($raceByVoie[$v])) {
                        foreach ($raceByVoie[$v] as $race) {
                            $race = strtolower($race);
                            if (!in_array($race, $dataTab)) {
                                $dataTab[] = $race;
                            }
                        }
                    }
                }
            }else{
                $json = sql::getJsonBdd("select value from botExtra where label='{$steps[$step]['param']}'");
                $dataTab = array_keys($json);
            }
            $out = [];
            foreach ($dataTab as $d){
                $out[] = [$d,($step+1)."-$id-$d"];
            }
            if($step >0){$out[] = ["Retour",($step-1)."-$id-Retour"];}

            $md->createSelect($msg,$out,$func);
        };
        $md->createSelect($msg,[['Site',"0-{$this->id}-Site"],['Discord',"0-{$this->id}-Discord"]],$func);
    }

    function datafiche($param){
        global $size;
        $id = $this->id;
        $champ = tools::sansAccent(strtolower(preg_split('/[\s]/', $param)[0]));


        $tab = [
            'age' => "Il faut un nombre",
            'caractere' => "minimum 200 caractères\n!dataFiche caractere\n@texte",
            'image' => "Mettre l'url",
            'name' => "Mettre le prenom suivi du nom, le prenom doit être unique",
            'objectif' => "minimum 200 caractères\n!dataFiche objectif\n@texte",
            'donName' => 'Indiquer le nom de votre don',
            'donDescription' => "!dataFiche donDescription\n@texte",
            'donEveil' => "!dataFiche donEveil\n@texte",
            'donTranscendance' => "!dataFiche donTranscendance\n@texte",
            'donComp' => "Des informations complémentaires ? (facultatif)\n!dataFiche donComp\n@texte",
            'story' => "plusieurs chapitres possible, Le cumul des chapitres doit faire minimum 500 caractères (encore xxx)\n!dataFiche story nom chapitre\n@texte"
        ];

        $array = array_map('strtolower',array_keys($tab));
        if(!empty($champ)){
            $champMaj = [
                'donname' => 'donName',
                'dondescription' => 'donDescription',
                'doneveil' => 'donEveil',
                'dontranscendance' => 'donTranscendance',
                'doncomp' => 'donComp'
            ];
            if(!in_array($champ,$array)){
                return "Le paramètre $param est inconnu";
            }
            $text = substr($param,strlen($champ)+1);
            $textArray = explode("\n",$text);
            $data = substr($text,strlen($textArray[0])+1);
            if($champ == "name"){
                $sql = "select 1 from perso where prenom like '" . explode(' ', $text)[0] . " %'";
                if (sql::fetch($sql)) {
                    return "Le premier mot de votre prénom est déjà utilisé.";
                }
            }
            if($champ == "age" && !is_numeric($text) && $text > 0){return "L'âge doit être écrit en nombre";}
            $taille = strlen(trim($data));
            if(in_array($champ,['caractere','objectif']) && $taille < 200){return "encore au moins ".(200-$taille)." caractères";}
            if(in_array($champ,['dondescription','doneveil','dontranscendance','doncomp','story']) && $taille < 50){return "encore au moins ".(50-$taille)." caractères";}

            if($champ == "story"){
                $nbr = sql::fetch("select COUNT(1) FROM ficheData WHERE idPerso='$id' AND label LIKE 'text-story-%'")[0];
                $title = trim($textArray[0]);
                sql::query("insert into ficheData values('$id','title-story-$nbr','$title',now()) ON DUPLICATE KEY UPDATE value='$title',dateInsert=now()");
                $champ = "text-story-$nbr";
            }

            $champ = empty($champMaj[$champ]) ? $champ : $champMaj[$champ];
            $text = addslashes(trim(count($textArray) > 1 ? $data : $text));
            sql::query("insert into ficheData values('$id','$champ','$text',now()) ON DUPLICATE KEY UPDATE value='$text',dateInsert=now()");


        }
        $msg = "> **__Avancement__**\nremplacer **@texte** par le contenu après un retour à la ligne(retour à la ligne possible dans le contenu)\n```xml\n";

        $results = sql::fetchAll("select label,value FROM ficheData WHERE idPerso='$id'");
        $exist = [];
        foreach ($results as $result){
            $exist[$result[0]] = $result[1];
        }

        foreach ($tab as $id=>$val){
            if($id=="story"){
                $size = 0;
                $countStory = function ($v,$k){global $size;if(strpos($k,"ext-story"))$size+=strlen($v);};
                array_walk($exist,$countStory);
                if($size < 500){
                    $msg.= "<$id>\n".str_replace("xxx",500-$size,$val).!"\n\n";
                }
            }elseif(empty($exist[$id])){
                $msg.= "<$id>\n$val\n\n";
            }
        }
        $msg .= "```\nEx: !dataFiche age 22";
        return $msg;
    }

    function newtexte($param){
        $data = $this->_TraitementData($param,['id','texte']);
        if(count($data) != 2){return $this->help("newtexte");}
        trad::editTrad($data['id'],$data['texte'],$this->isAdmin);
        return _t("newtexte",$data['id']);
    }

}