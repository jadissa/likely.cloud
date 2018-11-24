<?php

defined( 'base_directory' ) or die( 'This is not a valid job' );

$CONFIGURATION 	= [
    'sticky'    => true,
    'driver'    => 'mysql',
    'host'      => $SETTINGS->database[0]->mysql[0]->host,
    'database'  => $SETTINGS->database[0]->mysql[0]->db_name,
    'username'  => $SETTINGS->database[0]->mysql[0]->user_name,
    'password'  => $SETTINGS->database[0]->mysql[0]->pass_word,
    'charset'   => $SETTINGS->database[0]->mysql[0]->charset,
    'collation' => $SETTINGS->database[0]->mysql[0]->collation,
];


//
//  Register connection
//
$CAPSULE                = new \Illuminate\Database\Capsule\Manager;

$CAPSULE->addConnection( $CONFIGURATION );

$CAPSULE->setAsGlobal();

$CAPSULE->bootEloquent();