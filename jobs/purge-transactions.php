<?php

//
//  /var/www/html/likely.cloud/jobs/purge-transactions.php
//

//
//  Settings
//
define( 'base_directory', '/var/www/html/likely.cloud' );

$FROM_DATE  = date( 'Y-m-d H:i:s', time() - ( 60 * 15 ) );


//
//  Get initial settings
//
$settings   = file_get_contents( base_directory . '/app/settings.json' );

if( empty( $settings ) ) die( 'Improperly configured ' . __FILE__ . PHP_EOL );

$SETTINGS   = json_decode( $settings );


//
//  Requirements
//
require_once base_directory . '/vendor/autoload.php';

use App\models\service;

use App\models\transaction;

require_once base_directory . '/jobs/connection.php';


//
//  Fetch the active services
//
$ACTIVE_SERVICES    = service::fetchActive();


//
//  Get the transactions within range
//
foreach( $ACTIVE_SERVICES as $SERVICE ) {

    $TRANSACTIONS   = transaction::fetchByServiceDate( $SERVICE->id, $FROM_DATE );

    if( empty( $TRANSACTIONS) ) die();


    //
    //  Delete what is found
    //
    foreach( $TRANSACTIONS as $TRANSACTION ) {

        $deleted    = $TRANSACTION->delete();

    }

}