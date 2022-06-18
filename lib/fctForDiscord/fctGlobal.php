<?php
use Discord\Builders\MessageBuilder;

class fctGlobal extends structure {

    public function __construct()
 {
        $this->required = '';
    }

    function new_char() {
        global $md;
        if ( !empty( sql::fetch( "select 1 from perso where idPerso='{$this->id}a'" ) ) ) {
            return _t( 'newChar.already' );
        }
        $msg = _t( 'newChar.welcom' ).'\n\n';
        $msg .= _t( 'newChar.begin', '```xml\n', '\n```' );

        $func = function ( $interaction, $options ) use ( &$func ) {
            global $md;

            $idUserInter = $interaction->user->id;
            $msgDefault = _t( 'newChar.welcom' ).'\n\n';
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
                    'param' => 'vPrimaire',
                    'bddBefore' => 'genre'
                ],
                2 => [
                    'msg' => _t( 'newChar.voieS' ),
                    'param' => 'vPrimaire',
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
                $result = sql::fetchAll( "SELECT value FROM ficheData WHERE idPerso='$id' AND label IN ('vPrimaire','vSecondaire')" );
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

            $md->createSelect( $msg, $out, $func );
        }
        ;
        $md->createSelect( $msg, [ [ 'Site', "0-{$this->id}-Site" ], [ 'Discord', "0-{$this->id}-Discord" ] ], $func );
    }

    function datafiche( $param ) {
        global $size;
        $id = $this->id;
        $champ = tools::sansAccent( strtolower( preg_split( '/[\s]/', $param )[ 0 ] ) );

        $tab = [
            'age',
            'caractere',
            'image',
            'name',
            'objectif',
            'donName',
            'donDescription',
            'donEveil',
            'donTranscendance',
            'donComp',
            'story'
        ];
        $array = array_map( 'strtolower', $tab );
        if ( !empty( $champ ) ) {
            $champMaj = [
                'donname' => 'donName',
                'dondescription' => 'donDescription',
                'doneveil' => 'donEveil',
                'dontranscendance' => 'donTranscendance',
                'doncomp' => 'donComp'
            ];
            if ( !in_array( $champ, $array ) ) {
                return _t( 'dataFiche.unknown', $param );
            }
            $text = substr( $param, strlen( $champ )+1 );
            $textArray = explode( '\n', $text );
            $data = substr( $text, strlen( $textArray[ 0 ] )+1 );
            if ( $champ == 'name' ) {
                $sql = "select 1 from perso where prenom like '" . explode( ' ', $text )[ 0 ] . " %'";
                if ( sql::fetch( $sql ) ) {
                    return _t( 'dataFiche.errorName' );
                }
            }
            if ( $champ == 'age' && !is_numeric( $text ) && $text > 0 ) {
                return _t( 'dataFiche.errorAge' );
            }
            $taille = strlen( trim( $data ) );
            if ( in_array( $champ, [ 'caractere', 'objectif' ] ) && $taille < 200 ) {
                return _t( 'dataFiche.errorNbrChar', ( 200-$taille ) );
            }
            if ( in_array( $champ, [ 'dondescription', 'doneveil', 'dontranscendance', 'doncomp', 'story' ] ) && $taille < 50 ) {
                return _t( 'dataFiche.errorNbrChar', ( 50-$taille ) );
            }

            if ( $champ == 'story' ) {
                $nbr = sql::fetch( "select COUNT(1) FROM ficheData WHERE idPerso='$id' AND label LIKE 'text-story-%'" )[ 0 ];
                $title = trim( $textArray[ 0 ] );
                sql::query( "insert into ficheData values('$id','title-story-$nbr','$title',now()) ON DUPLICATE KEY UPDATE value='$title',dateInsert=now()" );
                $champ = "text-story-$nbr";
            }

            $champ = empty( $champMaj[ $champ ] ) ? $champ : $champMaj[ $champ ];
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
            if ( $id == 'story' ) {
                $size = 0;
                $countStory = function ( $v, $k ) {
                    global $size;
                    if ( strpos( $k, 'ext-story' ) )$size += strlen( $v );
                }
                ;
                array_walk( $exist, $countStory );
                if ( $size < 500 ) {
                    $msg .= "<$id>\n".str_replace( 'xxx', 500-$size, $val ).!'\n\n';
                }
            } elseif ( empty( $exist[ $id ] ) ) {
                $msg .= "<$id>\n$val\n\n";
            }
        }
        $msg .= '```\n'._t( 'dataFiche.exemple' );
        return $msg;
    }
  
      public function daterp()
 {
        /*récupérer les valeurs suivantes
        - jour de début du RP, valeur IRL car on va utiliser la date reelle pour le calcul on evite une conversion supplementaire de IRL vers fictif
        - vitesse d'écoulement pour un jour IRL (1 = 1 jour/jour IRL, 0.5 = 0,5 jour pour 1 IRL)
        */
        $rawDatas = sql::fetchAll("SELECT label,value FROM botExtra WHERE label in ('baseJourIRL','joursRpParJourIRL')");
        foreach ($rawDatas as $key) {
           $datas[$key['label']]=$key['value'];
        }
       //gestion des valeurs interdites
        if (!strtotime($datas['baseJourIRL'])||!is_numeric($datas['joursRpParJourIRL'])) {
            return (_t('global.error')." "._t('global.baseCorrompue')." "._t("daterp.calculImpossible"));
        }
        //création des objets dates
        $dateDebutRP = new DateTime($datas['baseJourIRL']);
        $dateActuelle = new DateTime('now'); 
        if ($dateDebutRP>$dateActuelle||$datas['joursRpParJourIRL']> 10000||$datas['joursRpParJourIRL']<=0) {
            return (_t('global.error')." "._t('global.baseCorrompue')." "._t("daterp.limiteTech"));
        }
        // calculer la différence entre date actuelle et jour du début du RP : en seconde        
        $différenceDates = $dateActuelle->getTimestamp() - $dateDebutRP->getTimestamp();
        //appliquer le multiplicateur et ajouter le temps fictif
        $dateMultipliée = $différenceDates* $datas['joursRpParJourIRL'];        
        $dateModifiée = $dateDebutRP->add(new DateInterval('PT'.$dateMultipliée.'S'));
        // retourner une date
        return (_t('daterp.success',$dateModifiée->format('d/m/Y'),$dateModifiée->format('H:i:s' ) ) );
}
      function newtexte($param){
        $data = $this->_TraitementData($param,['id','texte']);
        if(count($data) != 2){return $this->help("newtexte");}
        trad::editTrad($data['id'],$data['texte'],$this->isAdmin);
        return _t("newtexte",$data['id']);
    }
}