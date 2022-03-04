<?php

/*
Classe static pour les requètes
*/
class sql
{
    private static $bdd;
    private static $oldAcces;
    private static $timeReco = 120;

    private static function connexion(){
        global $dataBdd;

        self::$bdd = null;
        try
        {
            self::$bdd = new PDO("mysql:host={$dataBdd['host']};dbname={$dataBdd['dbname']};charset=utf8", $dataBdd['user'], $dataBdd['passwd']);
        }
        catch(Exception $e)
        {
            die('Erreur : '.$e->getMessage());
        }
    }


    public static function query($qry){
        // Si connexion trop vielle ou non faite, reconnecte la bdd.
        if(empty(self::$bdd) || !empty(self::$oldAcces) && (time() - self::$oldAcces) > self::$timeReco)
            self::connexion();

        $result = self::$bdd->query($qry);
        self::$oldAcces = time();
        return $result;
    }

    public static function fetch($qry){
        $result = self::query($qry);
        return $result->fetch();
    }

    public static function fetchAll($qry){
        $result = self::query($qry);
        return $result->fetchAll();
    }

    public static function getJsonBdd ($qry){
        $r = sql::fetch($qry);
        return empty($r) ? false : json_decode($r[0],true);
    }
}