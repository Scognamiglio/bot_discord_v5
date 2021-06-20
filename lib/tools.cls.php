<?php
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
}