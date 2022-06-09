<?php

/*
boite a outil 
*/
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
}