<?php

/*
boite a outil 
*/
$tab;
class tools

{
    public static function prepareInsert($table,$param) {

        $qry = false;
        if(is_array($param) && !empty($table)){
            $ls = [];
            $vs = [];
            foreach ($param as $l=>$v){
                $ls[] = $l;$vs[] = $v;
            }
            $qry = "insert into $table(".implode(',',$ls).") values ('".implode("','",$vs)."')";
        }

        return $qry;
    }

    public static function sansAccent($str){
        $str = htmlentities($str, ENT_NOQUOTES, 'utf-8');
        $str = preg_replace('#&([A-za-z])(?:acute|cedil|caron|circ|grave|orn|ring|slash|th|tilde|uml);#', '\1', $str);
        $str = preg_replace('#&([A-za-z]{2})(?:lig);#', '\1', $str); // pour les ligatures e.g. 'œ'
        $str = preg_replace('#&[^;]+;#', '', $str);

        return $str;
    }

    public static function array_map_assoc(callable $f, array $a) {
        return array_column(array_map($f, array_keys($a), $a), 1, 0);
    }
    /**
     *
     */
    public static function alias($act)
    {
        global $tab;
        if (empty($tab)) {
            foreach (sql::fetchAll("SELECT * from alias") as $ligne ) {
                $tab[$ligne["original"]] = array_map("trim",explode(",",$ligne["autres"])) ;
            }
        }
        //trouver si c'est une clé
        if (in_array($act,array_keys($tab))) {
            return $act;
        }
        foreach ($tab as $clef => $valeur ) {
            if (in_array($act,$valeur)) {
                return $clef;
            };
        }
        return $act;
    }

    public static function operation($op,$dyna){
        $dyna[',']='.';
        $dyna[' ']='';
        $op = str_replace(array_keys($dyna),array_values($dyna),$op);
        if(preg_match('/^[0-9+*\/\-\.]*$/',$op)){
            return eval("return $op;"); // !!!!! Ne jamais faire d'eval sans être sur à 100% du contenu de la variable !!!
        }
        //Potentiellement log car attaque XSS essayé.
        return 0;
    }
}