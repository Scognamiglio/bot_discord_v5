<?php

class fctChara extends structure {

    public function __construct()
    {
        $this->required = "fiche";
    }


    public function fiche($param){
        global $bdd,$cb;

        $chara = $bdd->query("select * from perso p INNER JOIN persoClasse pc ON p.idPerso=pc.idPerso where p.idPerso='{$this->id}'")->fetch();
        $stats = $cb->getStatsChar();
        $sqlt = [
            'Author' => $chara['prenom'],
            'Thumbnail' => $chara['avatar'],
            'Title' => "Classe : {$chara['classe']}\nNiveau : {$chara['niveau']}\n",
            "Description" => "> **__Informations générales__**\n\n**Experience** : {$chara['xp']}/1200\n**Race** : {$chara['race']}\n**Sexe** : {$chara['sexe']}\n\n> __**Voies**__\n\n**Arme** : {$chara['arme']} (niv. {$chara['armeLevel']})\n**Elément** : {$chara['element']} (niv. {$chara['elementLevel']})\n\n> **__Statistiques__**",
            "FieldValues" => [
                ["PV : {$stats['pv']}", "**ATK : {$stats['atk']}**","inline"],
                ["PM : {$stats['pm']}", "**INT : {$stats['int']}**","inline"]
            ],
            "Color" => "0x00AE86"
        ];
        $this->md->sendEmbed($sqlt);

    }

    public function pnj($param){
        global $bdd;
        $data = $this->_TraitementData($param,['alias','nom','image']);
        if(count($data)!=3){
            $this->help("pnj");return null;
        }
        $qry = "insert into pnj values ('{$data['alias']}','{$data['nom']}','{$data['image']}','{$this->id}') ON DUPLICATE KEY UPDATE name='{$data['nom']}',img='{$data['image']}'";
        $bdd->query($qry);
        return "Le PNJ a été créé ou mis à jour";


    }
}