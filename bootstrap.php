<?php

$settings_file  = $_SERVER['DOCUMENT_ROOT'] . '/settings.json';

if( !is_file( $settings_file ) ) {

    die( 'Not configured ' . __FILE__ );

}

$SETTINGS	=  json_decode( file_get_contents( $settings_file ) );

if( empty( $SETTINGS ) || json_last_error() != JSON_ERROR_NONE ) {

    die( 'Improperly configured ' . __FILE__  );

}