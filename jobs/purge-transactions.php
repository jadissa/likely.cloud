<?php

//
//  Get initial settings
//
require_once dirname(__FILE__) . '/../api/bootstrap.php';

if( empty( $SETTINGS ) ) die( 'Improperly configured ' . __FILE__ );


//
//  Requirements
//
require_once dirname(__FILE__) . '/../vendor/autoload.php';


//
//  Database
//
$DATABASE = new PDO( "mysql:host=" . $SETTINGS->database[0]->mysql[0]->host . ";dbname="
    . $SETTINGS->database[0]->mysql[0]->db_name, $SETTINGS->database[0]->mysql[0]->user_name, $SETTINGS->database[0]->mysql[0]->pass_word );

$DATABASE->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );

$DATABASE->setAttribute( PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC );

$DB         = new NotORM( $DATABASE );

$DB->debug  = $SETTINGS->debug;


//
//  Limit the timeframe to anything behind now - 15 minutes
//
$END_DATE  = date( 'Y-m-d H:i:s', time() - ( 60 * 15 ) );


//
//  Fetch the active services
//
$SERVICES   = $DB->services()->fetch();


//
//  Get the transactions within range
//
foreach( $SERVICES as $SERVICE ) {

    exit( var_dump( iterator_to_array( $SERVICE ) ) ) ;
    print'<pre>';print_r( $SERVICE );print'</pre>';exit;

    if( $SERVICE['status'] != 'active' )    continue;

    $TRANSACTIONS    = $DB->transactions()->where( 'created >= "' . date( 'Y-m-d H:i:s', time() - ( 60 * 15 ) ) . '"' )->where( ['sid' => $SERVICE['id'] ] )->order( 'created desc' );

    if( empty( $TRANSACTIONS) ) die();

    $TRANSACTIONS   = iterator_to_array( $TRANSACTIONS );

    foreach( $TRANSACTIONS as $TRANSACTION ) {

        print'<pre>';print_r( $TRANSACTION);print'</pre>';

    }

}
