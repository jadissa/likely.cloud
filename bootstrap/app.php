<?php

//
//  Fetch settings
//
require_once $_SERVER['DOCUMENT_ROOT'] . '/../vendor/autoload.php';
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Respect\Validation\Validator as v;

$settings_file  = $_SERVER['DOCUMENT_ROOT'] . '/../app/settings.json';

if( !is_file( $settings_file ) ) {

    die( 'Not configured ' );

}

$SETTINGS   = json_decode( file_get_contents( $settings_file ), true );

if( empty( $SETTINGS ) || json_last_error() != JSON_ERROR_NONE ) {

    die( 'Improperly configured ' . __FILE__  );

}

use App\models\session;
session::start( $SETTINGS['session'][0] );

$SETTINGS['displayErrorDetails']    = !empty( $SETTINGS['debug'] ) ? true : false;

$SETTINGS['api_hash']               = 'asdf';

ini_set( 'date.timezone', $SETTINGS['timezone'] );


//
//  Register Slim
//
$APP    = new \Slim\App( [
    'settings' => $SETTINGS,
] );


//
//	Register container
//
$CONTAINER              = $APP->getContainer();


//
//  Register connection
# https://laravel.com/docs/5.7/database#configuration
# https://github.com/illuminate/database
# https://laravel.com/docs/5.7/queries
//
$CAPSULE                = new \Illuminate\Database\Capsule\Manager;

$CAPSULE->addConnection( [
    'sticky'    => true,
    'driver'    => 'mysql',
    'host'      => $SETTINGS['database'][0]['mysql'][0]['host'],
    'database'  => $SETTINGS['database'][0]['mysql'][0]['db_name'],
    'username'  => $SETTINGS['database'][0]['mysql'][0]['user_name'],
    'password'  => $SETTINGS['database'][0]['mysql'][0]['pass_word'],
    'charset'   => $SETTINGS['database'][0]['mysql'][0]['charset'],
    'collation' => $SETTINGS['database'][0]['mysql'][0]['collation'],
] );

$CAPSULE->setAsGlobal();

$CAPSULE->bootEloquent();

$CONTAINER['db']  = function( $CONTAINER ) use( $CAPSULE ) {

    return $CAPSULE;

};


//
//  Append dependencies
//  # @todo: dynamic dependency injection
//
$CONTAINER['auth']      = function( $CONTAINER ) {

    return new \App\controlers\auth\auth( $CONTAINER );

};

$CONTAINER['flash']      = function( $CONTAINER ) {

    return new \Slim\Flash\Messages;

};

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

$CONTAINER['register']      = function( $CONTAINER ) {

    return new \App\controlers\register( $CONTAINER );

};

$CONTAINER['login']      = function( $CONTAINER ) {

    return new \App\controlers\login( $CONTAINER );

};

$CONTAINER['logout']      = function( $CONTAINER ) {

    return new \App\controlers\logout( $CONTAINER );

};

$CONTAINER['home']      = function( $CONTAINER ) {

	return new \App\controlers\home( $CONTAINER );

};

$CONTAINER['preference']      = function( $CONTAINER ) {

    return new \App\controlers\users\preference( $CONTAINER );

};

$CONTAINER['service']      = function( $CONTAINER ) {

    return new \App\controlers\users\service( $CONTAINER );

};

$CONTAINER['export']      = function( $CONTAINER ) {

    return new \App\controlers\users\export( $CONTAINER );

};

$CONTAINER['content']      = function( $CONTAINER ) {

    return new \App\controlers\users\content( $CONTAINER );

};

$CONTAINER['email']      = function( $CONTAINER ) {

    return new \App\controlers\services\email( $CONTAINER );

};

$CONTAINER['tumblr']      = function( $CONTAINER ) {

    return new \App\controlers\services\tumblr( $CONTAINER );

};

$CONTAINER['validator'] = function( $CONTAINER ) {

    return new \App\validation\validator;

};

$CONTAINER['csrf']      = function( $c ) {

    return new \Slim\Csrf\Guard(
        'csrf',
        $storage,
        null,
        200,
        16,
        true
    );

};

$CONTAINER['csrf']->setFailureCallable(function ($request, $response, $next) {

    $request = $request->withAttribute("csrf_status", false);

    return $next($request, $response);

});;

$CONTAINER['logger']    = function() use( $SETTINGS ) {

    $LOGGER = new \Monolog\Logger( $SETTINGS['log'][0]['name'] );

    $FH = new \Monolog\Handler\StreamHandler( $SETTINGS['log'][0]['endpoint'] );

    $LOGGER->pushHandler( $FH );

    return $LOGGER;

};

$APP->add( new \App\middleware\csrf( $CONTAINER, $SETTINGS ) );
$APP->add( $CONTAINER->get( 'csrf' ) );

$APP->add( new \App\middleware\validation( $CONTAINER, $SETTINGS ) );

$APP->add( new \App\middleware\input( $CONTAINER, $SETTINGS ) );

$APP->add( new \App\middleware\flash( $CONTAINER, $SETTINGS ) );

# @todo: implement custom rules
#v::with( 'App\\validation\\rules\\' );


require_once $_SERVER['DOCUMENT_ROOT'] . '/../app/routes.php';