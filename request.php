<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/bootstrap.php';


//
//  Inspect the header
//
$HEADERS    = getallheaders();


//
//  Verify request referer
//
if( empty( $HEADERS['Referer'] ) ) {

    exit();

}


//
//  Verify settings
//
if( empty( $SETTINGS ) ) {

    exit( json_encode( ['stat' => false, 'message' => 'Not configured' ] ) );

}


//
//  Validator registry
//
$REQUEST_VALIDATORS = [
    'services'      => [
        'route'     => '/services',
        'type'      => 'GET',
        'PARAMS'    => [],
        'REQUIRED'  => [],
    ],
    'register'      => [
        'route'     => '/register',
        'type'      => 'POST',
        'PARAMS'    => [
            'uname',
            'pwd',
            'route',
            'status',
            'validator',
            'submit',
        ],
        'REQUIRED'  => [
            'route',
        ],
    ],
    'tumblr'      => [
        'route'     => '/tumblr',
        'type'      => 'POST',
        'PARAMS'    => [
            'route',
            'status',
            'validator',
            'submit',
        ],
        'REQUIRED'  => [
            'route',
        ],
    ],
];


//
//  Capture request
//
$REQUEST    = $_REQUEST;


//
//  Validate request is internal
//
if( empty( stristr( $HEADERS['Referer'], ( !empty( $SETTINGS->using_https ) ? 'https://' : 'http://' ) . $SETTINGS->domain ) ) ) {

    header( 'Location: ' . $HEADERS['Referer'] );

    exit();

}


//
//  Verify request validator
//
if( empty( $REQUEST_VALIDATORS[ $REQUEST['validator'] ] ) ) {

    header( 'Location: ' . $HEADERS['Referer'] );

    exit();

}


//
//  Verify request params
//
if( !empty( array_diff( array_keys( $REQUEST ), $REQUEST_VALIDATORS[ $REQUEST['validator'] ]['PARAMS'] ) ) ) {

    header( 'Location: ' . $HEADERS['Referer'] );

    exit();

}


//
//  Format request
//
$FORMATTED_REQUEST      = $REQUEST;

if( !empty( $FORMATTED_REQUEST['pwd'] ) )   $FORMATTED_REQUEST['pwd']   = 'withheld';

$REQUEST['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'];

$REQUEST['route']       = $REQUEST_VALIDATORS[ $REQUEST['validator'] ]['route'];


//
//  Log request
//
$LOGGER     = new \Monolog\Logger( $SETTINGS->log[0]->name );

$FH         = new \Monolog\Handler\StreamHandler( $_SERVER['DOCUMENT_ROOT'] . $SETTINGS->log[0]->endpoint );

$LOGGER->pushHandler( $FH );

$LOGGER->addInfo( serialize( $FORMATTED_REQUEST ) );


//
//  Make request
//
$API_REQUEST        = new \service\api\request( $SETTINGS, $REQUEST['route'], $_SERVER['REQUEST_METHOD'], $REQUEST );

$PARSED_RESPONSE    = $API_REQUEST->parse();

$LOGGER->addInfo( serialize( $PARSED_RESPONSE ) );


//
//  Redirect, exit with response or exit
//
if ( !empty( $PARSED_RESPONSE ) and isset( $PARSED_RESPONSE->response->stat ) and !empty( $PARSED_RESPONSE->response->message ) and !empty( $PARSED_RESPONSE->response->redirect ) ) {

     header( 'Location: ' . $PARSED_RESPONSE->response->redirect );

} else {

    header( 'Location: ' . $HEADERS['Referer'] );

}

exit();