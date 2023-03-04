<?php


class trad
{
    private static $cache;
    private static $lang;
    public static function init(){
        self::$lang = $lang = 'FR';
        $qry = "select id,texte from trad where lang='$lang'";
        $c = [];
        foreach (sql::fetchAll($qry) as $d){
            $c[$d['id']] = $d['texte'];
        }
        self::$cache=$c;
    }

    public static function getTrad($id){
        if(isset(self::$cache[$id])){
            return self::$cache[$id];
        }else{
            $lang = self::$lang;
            $qry = "select texte from trad where lang='$lang' and id='$id'";
            $r = sql::fetch($qry);
            if(empty($r)){
                return false;
            }else{
                self::$cache[$id] = $r['texte'];
                return $r['texte'];
            }
        }
        // Rajouter la requète !
        return null;
    }

    public static function editTrad($id,$texte,$valid=false){
        $lang = self::$lang;
        $qry = "select texte from trad where lang='$lang' and id='$id'";
        $r = sql::fetch($qry);
        if(empty($r)) {
            return false;
        }
        $champ = $valid ? "texte" : "textePropo";
        $qry = "update trad set $champ='$texte' where lang='$lang' and id='$id'";
        sql::query($qry);
        if($valid){
            self::$cache[$id] = $texte;
        }
    }
}

function _t($id,...$array){
    $word = empty($array) ? trad::getTrad($id) : sprintf(trad::getTrad($id),...$array);
    $word = str_replace("\\n","\n",$word);
    return $word;
}