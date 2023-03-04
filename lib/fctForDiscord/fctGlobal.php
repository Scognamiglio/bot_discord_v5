<?php
use Discord\Builders\MessageBuilder;

class fctGlobal extends structure {

    public function __construct() {
        $this->required = '';
    }

    function new_char() {
        if ( !empty( sql::fetch( "select 1 from perso where idPerso='{$this->id}a'" ) ) ) {
            return _t( 'newChar.already' );
        }
        $msg = _t( 'newChar.welcome' )."\n\n";
        $msg .= _t( 'newChar.begin', '```xml\n', '\n```' );

        $func = function ( $interaction, $options ) use ( &$func ) {

            $idUserInter = $interaction->user->id;
            $msgDefault = _t( 'newChar.welcome' ).'\n\n';
            $msgDefault .= _t( 'newChar.begin', '```xml\n', '\n```' );
            $msgError = '> ***__'._t( 'newChar.notUser' ).'.__***\n\n';
            $steps = [
                -1 => [
                    'msg' => $msgDefault,
                    'param' => 'newChar',
                ],
                0 => [
                    'msg' => _t( 'newChar.genre' ),
                    'param' => 'genre',
                ],
                1 => [
                    'msg' => _t( 'newChar.voieP' ),
                    'param' => 'vAll',
                    'bddBefore' => 'genre'
                ],
                2 => [
                    'msg' => _t( 'newChar.voieS' ),
                    'param' => 'vAll',
                    'bddBefore' => 'vPrimaire'
                ],
                3 => [
                    'msg' => _t( 'newChar.race' ),
                    'param' => 'race',
                    'bddBefore' => 'vSecondaire',
                ]
            ];

            $selected = $options[ 0 ]->getValue();
            $label = $options[ 0 ]->getLabel();
            if ( $label == 'Site' ) {
                return $interaction->updateMessage( MessageBuilder::new()->setContent( _t( 'newChar.site' ) ) );
            }
            $arrayData = explode( '-', $selected );
            $step = $arrayData[ 0 ];
            $id = $arrayData[ 1 ];
            $error = $id != $idUserInter;
            if ( $error ) {
                $step = $step + ( $label == 'Retour' ? 1 : -1 );
            } elseif ( !empty( $steps[ $step ][ 'bddBefore' ] ) && $label != 'Retour' ) {
                sql::query( "insert into ficheData values('$id','{$steps[$step]['bddBefore']}','{$arrayData[2]}',now()) ON DUPLICATE KEY UPDATE value='{$arrayData[2]}',dateInsert=now()" );
            }
            if ( empty( $steps[ $step ] ) && !$error ) {
                $arrayData[ 2 ] = ucfirst( $arrayData[ 2 ] );
                sql::query( "insert into ficheData values('$id','race','{$arrayData[2]}',now()) ON DUPLICATE KEY UPDATE value='{$arrayData[2]}',dateInsert=now()" );
                return $interaction->updateMessage( MessageBuilder::new()->setContent( _t( 'newChar.finish' ) ) );
            }
            $msg = ( $error ? $msgError : '' ).$steps[ $step ][ 'msg' ];

            if ( $steps[ $step ][ 'param' ] == 'race' ) {
                $raceByVoie = sql::getJsonBdd( "select value from botExtra where label='raceByVoie'" );
                $array = [ 'all' ];
                $dataTab = [];
                $result = sql::fetchAll( "SELECT value FROM ficheData WHERE idPerso='$id' AND label in ('vPrimaire','vSecondaire')" );
                foreach ( $result as $v ) {
                    $array[] = $v[ 0 ];
                }

                foreach ( $array as $v ) {
                    $v = strtolower( $v );
                    if ( !empty( $raceByVoie[ $v ] ) ) {
                        foreach ( $raceByVoie[ $v ] as $race ) {
                            $race = strtolower( $race );
                            if ( !in_array( $race, $dataTab ) ) {
                                $dataTab[] = $race;
                            }
                        }
                    }
                }
            } else {
                $json = sql::getJsonBdd( "select value from botExtra where label='{$steps[$step]['param']}'" );
                $dataTab = array_keys( $json );
            }
            $out = [];
            foreach ( $dataTab as $d ) {
                $out[] = [ $d, ( $step+1 )."-$id-$d" ];
            }
            if ( $step >0 ) {
                $out[] = [ 'Retour', ( $step-1 )."-$id-Retour" ];
            }

            $GLOBALS['md']->createSelect( $msg, $out, $func );
        }
        ;
        $GLOBALS['md']->createSelect( $msg, [ [ 'Site', "0-{$this->id}-Site" ], [ 'Discord', "0-{$this->id}-Discord" ] ], $func );
    }

    function datafiche( $param ) {
        global $size;
        $id = $this->id;
        $champ = tools::sansAccent( strtolower( preg_split( '/[\s]/', $param )[ 0 ] ) );

        $tab = [
            'age',
            'caractere',
            'image',
            'physique',
            'name',
            'objectif',
            'don',
            'story'
        ];
        $array = array_map( 'strtolower', $tab );
        if ( !empty( $champ ) ) {
            if ( !in_array( $champ, $array ) ) {
                return _t( 'dataFiche.unknown', $param );
            }
            $text = substr( $param, strlen( $champ )+1 );
            $textArray = explode( "\n", $param );
            $data = substr( $param, strlen( $textArray[ 0 ] )+1 );
            if ( $champ == 'name' ) {
                $sql = "select 1 from perso where prenom like '" . explode( ' ', $text )[ 0 ] . " %'";
                if ( sql::fetch( $sql ) ) {
                    return _t( 'dataFiche.errorName' );
                }
            }

            if ( $champ == 'age' && (!is_numeric( $text ) || $text < 16 )) {
                return _t( 'dataFiche.errorAge' );
            }
            $taille = strlen( trim( $data ) );
            if ( in_array( $champ, [ 'caractere', 'objectif' ] ) && $taille < 200 ) {
                return _t( 'dataFiche.errorNbrChar', ( 200-$taille ) );
            }

            if ( $champ == 'don' ) {
                $nbr = sql::fetch( "select COUNT(1) FROM ficheData WHERE idPerso='$id' AND label LIKE 'text-don-%'" )[ 0 ];
                $title = addslashes(trim( explode("\n",$text)[0] ));
                sql::query( "insert into ficheData values('$id','title-story-$nbr','$title',now()) ON DUPLICATE KEY UPDATE value='$title',dateInsert=now()" );
                $champ = "text-don-$nbr";
            }

            if ( $champ == 'story' ) {
                $nbr = sql::fetch( "select COUNT(1) FROM ficheData WHERE idPerso='$id' AND label LIKE 'text-story-%'" )[ 0 ];
                $title = addslashes(trim( explode("\n",$text)[0] ));
                sql::query( "insert into ficheData values('$id','title-story-$nbr','$title',now()) ON DUPLICATE KEY UPDATE value='$title',dateInsert=now()" );
                $champ = "text-story-$nbr";
            }

            $text = addslashes( trim( count( $textArray ) > 1 ? $data : $text ) );
            sql::query( "insert into ficheData values('$id','$champ','$text',now()) ON DUPLICATE KEY UPDATE value='$text',dateInsert=now()" );

        }
        $msg = _t( 'dataFiche.msg' );

        $results = sql::fetchAll( "select label,value FROM ficheData WHERE idPerso='$id'" );
        $exist = [];
        foreach ( $results as $result ) {
            $exist[ $result[ 0 ] ] = $result[ 1 ];
        }

        foreach ( $tab as $id ) {
            $val = _t( "dataFiche.$id" );

            foreach (['story'=>500,'don'=>200] as $fieldWithChap => $sizeMin){
                if( $id == $fieldWithChap ){
                    $size = 0;
                    $countStory = function ( $v, $k ) use($fieldWithChap) {
                        global $size;
                        if ( strpos( $k, "ext-$fieldWithChap") )$size += strlen( $v );
                    }
                    ;
                    array_walk( $exist, $countStory );
                    if ( $size < $sizeMin ) {
                        $msg .= "<$id>\n".str_replace( 'xxx', $sizeMin-$size, $val ).!'\n\n';
                    }
                    continue 2;
                }
            }
            if ( empty( $exist[ $id ] ) ) {
                $msg .= "<$id>\n$val\n\n";
            }
        }
        return ($msg == _t( 'dataFiche.msg' )) ? _t('dataFiche.finish') : $msg."```\n"._t( 'dataFiche.exemple' );
    }
    public function daterp($params){
        global $jsonDateRP;
        $datedebutIRL = "baseJourIRL"; $mutiplicateur = "joursRpParJourIRL"; $dateRP = "dateRP";
        $jsonDateRP = (empty($jsonDateRP)) ? sql::getJsonBdd("SELECT value FROM botExtra WHERE label = 'daterp'") : $jsonDateRP ;
        $now = new DateTime('now');
        $intervalle =(new DateTime($jsonDateRP[$datedebutIRL]))->diff($now);
        $dateRPnew =  (new DateTime($jsonDateRP[$dateRP]))->add(new DateInterval("P".(int)floor(($intervalle->days)* $jsonDateRP[$mutiplicateur])."D"));
        $paramsArray = explode(" ",$params);
        $isAnUpdate = in_array($paramsArray[0],['set','x']);
        if($isAnUpdate  && !$this->isAdmin){
                return _t("global.notAdmin");
        }
        switch ($paramsArray[0]) {
            case 'set':
                $analysedate = explode("-",$paramsArray[1]);
                foreach ($analysedate as $part) {
                    if (!ctype_digit($part)) {
                        return (_t(__FUNCTION__.".argumentIncorrect"));
                    }
                }
                if(count($analysedate)!=3){
                    return (_t(__FUNCTION__.".argumentIncorrect"));
                }                
                $jsonDateRP[$datedebutIRL] = ($now)->format("Y-m-d");
                $jsonDateRP[$dateRP] = $paramsArray[1];
                $contenu = (new DateTime($jsonDateRP[$dateRP]))->format('d/m/Y');
                break;
            case 'x':
                if (!is_numeric($paramsArray[1])) {
                    return (_t(__FUNCTION__.".argumentIncorrect"));
                }
                if ($paramsArray[1]>999||$paramsArray[1]<=0) {
                    return (_t(__FUNCTION__.".limiteTech"));
                }                    
                $clemessage = "x"; 
                $jsonDateRP[$datedebutIRL] = ($now)->format("Y-m-d");
                $jsonDateRP[$dateRP] = $dateRPnew->format("Y-m-d");
                $jsonDateRP[$mutiplicateur] = $paramsArray[1];
                $contenu =$jsonDateRP[$mutiplicateur]; 
                break;
            default :
                $clemessage = "success";
                $contenu = $dateRPnew->format('d/m/Y');
                break;
        }
        ($isAnUpdate) ?  sql::query("UPDATE botExtra SET VALUE ='".json_encode(($jsonDateRP))."'WHERE label = 'daterp'") : NULL;
        return (_t(__FUNCTION__.'.'.($isAnUpdate  ? $paramsArray[0] : 'success'),$contenu));
    }

    function newtexte($param){
        $data = $this->_TraitementData($param,['id','texte']);
        if(count($data) != 2){return $this->help("newtexte");}
        trad::editTrad($data['id'],$data['texte'],$this->isAdmin);
        return _t("newtexte",$data['id']);
    }
}