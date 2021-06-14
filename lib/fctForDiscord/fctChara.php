<?php

class fctChara extends structure {


    public function fiche($param){
        global $md;
        $cb = new combat();

        $chara = $this->bdd->query("select * from perso p INNER JOIN persoClasse pc ON p.idPerso=pc.idPerso where p.idPerso='{$this->id}'")->fetch();
        $stats = $cb->get_stat();
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
        $this->message->channel->sendEmbed($md->createEmbed($sqlt));

    }
}