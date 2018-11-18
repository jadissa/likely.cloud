<?php

//
//  Requirements
//
require_once $_SERVER['DOCUMENT_ROOT'] . '/../vendor/autoload.php';
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

$settings_file  = $_SERVER['DOCUMENT_ROOT'] . '/../app/settings.json';

if( !is_file( $settings_file ) ) {

    die( 'Not configured ' . $settings_file );

}

$SETTINGS   = json_decode( file_get_contents( $settings_file ), true );

if( empty( $SETTINGS ) || json_last_error() != JSON_ERROR_NONE ) {

    die( 'Improperly configured ' . __FILE__  );

}

$SETTINGS['displayErrorDetails']    = !empty( $SETTINGS['debug'] ) ? true : false;


//
//	User
//
#$USER 	= new \App\models\user;

//
//  Slim
//
$APP    = new \Slim\App( [
    'settings' => $SETTINGS,
] );


//
//	Controler container
//
$CONTAINER              = $APP->getContainer();


//
//  Append view
//
$CONTAINER['view']	= function( $CONTAINER ) {

	$VIEW 	= new \Slim\Views\Twig( $_SERVER['DOCUMENT_ROOT'] . '/../resources/views', [

		# @todo: turn into a directory where cached views will be stored
		# https://www.slimframework.com/docs/v3/features/templates.html
		'cache'	=> false,

	] );

	$ROUTER 	= $CONTAINER->get( 'router' );

	$uri 		= \Slim\Http\Uri::createFromEnvironment( new \Slim\Http\Environment( $_SERVER ) );

	$VIEW->addExtension( new Slim\Views\TwigExtension( $ROUTER, $uri ) );

    if( !empty( $CONTAINER['settings']['debug'] ) ) {
        
        $VIEW->addExtension( new \Twig_Extension_Debug() );

    }

	return $VIEW;

};


//
//	Append controlers
//
$CONTAINER['home']      = function( $CONTAINER ) {

	return new \App\controlers\home( $CONTAINER );

};

$CONTAINER['auth']      = function( $CONTAINER ) {

	return new \App\controlers\auth\auth( $CONTAINER );

};

$CONTAINER['validator']      = function( $CONTAINER ) {

    return new \App\validation\validator;

};

$CONTAINER['services']  = function( $CONTAINER ) {

	return new \App\controlers\services( $CONTAINER );

};

$CONTAINER['feed']      = function( $CONTAINER ) {

    return new \App\controlers\listed\feed( $CONTAINER );

};


//
//  Append logging
//
$CONTAINER['logger']    = function() use( $SETTINGS ) {

    $LOGGER = new \Monolog\Logger( $SETTINGS['log'][0]['name'] );

    $FH = new \Monolog\Handler\StreamHandler( $SETTINGS['log'][0]['endpoint'] );

    $LOGGER->pushHandler( $FH );

    return $LOGGER;

};


//
//  Append db
//  https://clancats.io/hydrahon/master/sql-query-builder/select/basics
//
$CONTAINER['db']        = function( $CONTAINER ) use( $SETTINGS ) {

	$CONNECTION = new PDO( 
        'mysql:host=' . $SETTINGS['database'][0]['mysql'][0]['host'] . ";dbname=" . $SETTINGS['database'][0]['mysql'][0]['db_name'], 
        $SETTINGS['database'][0]['mysql'][0]['user_name'], 
        $SETTINGS['database'][0]['mysql'][0]['pass_word'], 
        [
            PDO::ATTR_PERSISTENT    => $SETTINGS['database'][0]['persist'],
            PDO::ATTR_ERRMODE       => !empty( $SETTINGS['debug'] ) ? PDO::ERRMODE_WARNING : PDO::ERRMODE_EXCEPTION,
        ]
    );

    $DATABASE = new \ClanCats\Hydrahon\Builder( 'mysql', function( $query, $queryString, $queryParameters ) use( $CONNECTION, $CONTAINER, $SETTINGS ) {

        if( !empty( $SETTINGS['debug'] ) ) {

            $CONTAINER['logger']->addInfo( serialize( $queryString ) );

        }

        $statement  = $CONNECTION->prepare( $queryString );

        $statement->execute( $queryParameters );

        if ( $query instanceof \ClanCats\Hydrahon\Query\Sql\FetchableInterface ) {

            return $statement->fetchAll( \PDO::FETCH_BOTH );

        }

    } );

    $DATABASE->c    = $CONNECTION;

    return $DATABASE;

};


//
//  Regiser validation
//
$APP->add( new \App\middleware\validation( $CONTAINER ) );


//
//  Register input
//
$APP->add( new \App\middleware\input( $CONTAINER ) );

require_once $_SERVER['DOCUMENT_ROOT'] . '/../app/routes.php';