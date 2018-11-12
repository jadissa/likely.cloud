<?php

$settings_file  = dirname(__FILE__) . '/settings.json';

if( !is_file( $settings_file ) ) {

    die( 'Not configured ' . __FILE__ );

}

$SETTINGS	=  json_decode( file_get_contents( $settings_file ) );

if( empty( $SETTINGS ) || json_last_error() != JSON_ERROR_NONE ) {

    die( 'Improperly configured ' . __FILE__  );

}

require_once dirname(__FILE__) . '/vendor/autoload.php';