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

    public static function alias($act = null)
    {
        if (!isset($GLOBALS['tableaualias'])) {
            $GLOBALS['tableaualias'] = sql::fetchAll("SELECT * from alias");
        }
        $tab =  $GLOBALS['tableaualias'] ;
        //donne la priorité aux commande native sur les alias
        foreach ($tab as $line){
            if ($line[0] === $act) {
               return $line[0];
            }
        }
        //verifiei la présence d'un alias compatible
        foreach ($tab as $line) {
            $values = explode(",",$line[1]);
            foreach ($values as $key)  {
                if (trim($key) === $act) {
                    return $line[0];
                }
            }            
        }            
    }        
}