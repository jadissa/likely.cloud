<?php

//
//  Get initial settings
//
require_once $_SERVER['DOCUMENT_ROOT'] . '/bootstrap.php';

if( empty( $SETTINGS ) ) die( 'Improperly configured ' . __FILE__ );


//
//  Headers/Session
//
header( 'Access-Control-Allow-Origin: *' );
header( 'Content-type: application/json; charset=utf-8' );


//
//  Requirements
//
require_once $_SERVER['DOCUMENT_ROOT'] . '/../vendor/autoload.php';
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;


//
//  Initial configuration
//
$CONFIG['displayErrorDetails']      = $SETTINGS->debug;
$CONFIG['addContentLengthHeader']   = false;
$CONFIG['debug']                    = $SETTINGS->debug;

/*
$CONFIG['db']['host']   = $SETTINGS['database']['mysql']['host'];
$CONFIG['db']['user']   = $SETTINGS['database']['mysql']['username'];
$CONFIG['db']['pass']   = $SETTINGS['database']['mysql']['password'];
$CONFIG['db']['dbname'] = $SETTINGS['database']['mysql']['database'];
*/


// Slim initialization
$APP = new \Slim\App( [
    'settings' => $CONFIG
] );


/*
// PDO/DB abstraction layer

$CONTAINER['db'] = function($c)
{
    $db = $c['settings']['db'];
    $pdo = new PDO("mysql:host=" . $db['host'] . ";dbname=" . $db['dbname'], $db['user'], $db['pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    return $pdo;
};
*/

/*
// Session initialization
$APP->add( new \Slim\Middleware\Session( [
    'name'          => $SETTINGS->session[0]->name,
    'autorefresh'   => $SETTINGS->session[0]->autorefresh,
    'lifetime'      => $SETTINGS->session[0]->lifetime,
] ) );


//
//  Session helper plugin
//
$CONTAINER['session'] = function () {

    return new \SlimSession\Helper;

};
*/


//
//  Plugin container
//
$CONTAINER = $APP->getContainer();


//
//  Provide container access to settings
//
$CONTAINER['SETTINGS']  = $SETTINGS;




//
//  Logging plugin
//
$CONTAINER['logger'] = function() use( $CONTAINER ) {

    $LOGGER = new \Monolog\Logger( $CONTAINER['SETTINGS']->log[0]->name );

    $FH = new \Monolog\Handler\StreamHandler( $_SERVER['DOCUMENT_ROOT'] . $CONTAINER['SETTINGS']->log[0]->endpoint );

    $LOGGER->pushHandler( $FH );

    return $LOGGER;

};


//
//  Request/location logging
//
$APP->post('/ping', function( ServerRequestInterface $REQUEST, ResponseInterface $RESPONSE ) use( $CONTAINER ) {

    $PARSED_REQUEST = $REQUEST->getParsedBody();

    $GEO = unserialize( file_get_contents( 'http://www.geoplugin.net/php.gp?ip=' . $PARSED_REQUEST['_SERVER']['REMOTE_ADDR'] ) );

    $REQUEST_DATA   = array(

        'HEADERS'           => $PARSED_REQUEST['_HEADERS'],

        'REMOTE_ADDR'       => $PARSED_REQUEST['_SERVER']['REMOTE_ADDR'],

        'QUERY_STRING'      => $PARSED_REQUEST['_SERVER']['QUERY_STRING'],

        'city'              => $GEO['geoplugin_city'],

        'state'             => $GEO['geoplugin_region'],

        'area_code'         => $GEO['geoplugin_areaCode'],

        'dma_code'          => $GEO['geoplugin_dmaCode'],

        'country_code'      => $GEO['geoplugin_countryCode'],

        'country_name'      => $GEO['geoplugin_countryName'],

        'continent_name'    => $GEO['geoplugin_continentName'],

        'latitude'          => $GEO['geoplugin_latitude'],

        'longitude'         => $GEO['geoplugin_longitude'],

        'timezone'          => $GEO['geoplugin_timezone'],

    );

    if( !empty( $CONTAINER['SETTINGS']->debug ) && $CONTAINER['SETTINGS']->visitor == $_SERVER['REMOTE_ADDR'] ) {

        print'<pre>';print_r( $PARSED_REQUEST );print'</pre>';

    }

    $CONTAINER['logger']->addInfo( serialize( $REQUEST_DATA ) );

    return $RESPONSE;

} );


//
//  Discord auth
//
$APP->get('/discord_auth', function( ServerRequestInterface $REQUEST, ResponseInterface $RESPONSE ) use( $CONTAINER ) {

    $PARSED_REQUEST = $REQUEST->getQueryParams();

    if( !empty( $CONTAINER['SETTINGS']->debug ) && $CONTAINER['SETTINGS']->visitor == $_SERVER['REMOTE_ADDR'] ) {

        print'<pre>';print_r( $PARSED_REQUEST );print'</pre>';

    }


    //
    //  Attempt token fetch
    //
    $CLIENT     = new GuzzleHttp\Client();

    $query_str = 'https://discordapp.com/api/v6/oauth2/token';

    $TOKEN_RESPONSE = $CLIENT->request( 'POST', $query_str, [
        'headers' => [
            'content-type: application/x-www-form-urlencoded',
        ],
        'form_params' => [
            'client_id'         => $CONTAINER['SETTINGS']->social[0]->discord[0]->client_id,
            'client_secret'     => $CONTAINER['SETTINGS']->social[0]->discord[0]->client_secret,
            'grant_type'        => 'authorization_code',
            'code'              => $PARSED_REQUEST['code'],
            'redirect_uri'      => !empty( $CONTAINER['SETTINGS']->using_https ) ? 'https://' : 'http://' . $CONTAINER['SETTINGS']->domain . '/' . $CONTAINER['SETTINGS']->social[0]->discord[0]->callback,
            'scope'             => $CONTAINER['SETTINGS']->social[0]->discord[0]->scope,
        ],
    ] );


    //
    //  Parse and check for failure
    //
    $PARSED_TOKEN_RESPONSE  = json_decode( $TOKEN_RESPONSE->getBody()->getContents() );

    if( !empty( $CONTAINER['SETTINGS']->debug ) && $CONTAINER['SETTINGS']->visitor == $_SERVER['REMOTE_ADDR'] ) {

        print'<pre>';print_r( $PARSED_TOKEN_RESPONSE );print'</pre>';

    }

    if( empty( $PARSED_TOKEN_RESPONSE->access_token ) ) {

        $REDIRECT_URL   = !empty( $CONTAINER['SETTINGS']->using_https ) ? 'https://' : 'http://' . $CONTAINER['SETTINGS']->parent_domain . '/signup.php?';

        $REDIRECT_DATA  = [
            'stat'      => false,
            'message'   => 'Sorry about this but we are unable to get you signed up through Discord at this time',
        ];

        $REDIRECT_URL   .= http_build_query( $REDIRECT_DATA );

        header( 'Location: ' . $REDIRECT_URL );

    }


    //
    //  Attempt user fetch
    //
    $CLIENT                 = new GuzzleHttp\Client();

    $query_str              = 'http://discordapp.com/api/users/@me';

    $USER_RESPONSE = $CLIENT->request( 'GET', $query_str, [
        'headers' => [
            'Authorization' => 'Bearer ' . $PARSED_TOKEN_RESPONSE->access_token,
        ],
    ] );


    //
    //  Parse and check for failure
    //
    $PARSED_USER_RESPONSE  = json_decode( $USER_RESPONSE->getBody()->getContents() );

    if( !empty( $CONTAINER['SETTINGS']->debug ) && $CONTAINER['SETTINGS']->visitor == $_SERVER['REMOTE_ADDR'] ) {

        print'<pre>';print_r( $PARSED_USER_RESPONSE );print'</pre>';

    }

    if( empty( $PARSED_USER_RESPONSE->id ) ) {

        $REDIRECT_URL   = !empty( $CONTAINER['SETTINGS']->using_https ) ? 'https://' : 'http://' . $CONTAINER['SETTINGS']->parent_domain . '/signup.php?';

        $REDIRECT_DATA  = [
            'stat'      => false,
            'message'   => 'Sorry about this but we are unable to get you signed up through Discord at this time',
        ];

        $REDIRECT_URL   .= http_build_query( $REDIRECT_DATA );

        header( 'Location: ' . $REDIRECT_URL );
    }

    $CONTAINER['logger']->addInfo( serialize( json_encode( $PARSED_USER_RESPONSE ) ) );


    //
    //  Normalize
    //
    $NORMALIZED_RESPONSE                        = [];

    $NORMALIZED_RESPONSE['discord_registry']    = $PARSED_USER_RESPONSE;

    if( !empty( $PARSED_USER_RESPONSE->id ) && !empty( $PARSED_USER_RESPONSE->avatar ) ) {

        $NORMALIZED_RESPONSE['discord_registry']->avatar  = 'https://discordapp.com/api/v6/users/' . $PARSED_USER_RESPONSE->id . '/avatars/' . $PARSED_USER_RESPONSE->avatar . '.jpg';

    }

    if( !empty( $CONTAINER['SETTINGS']->debug ) && $CONTAINER['SETTINGS']->visitor == $_SERVER['REMOTE_ADDR'] ) {

        print'<pre>';print_r( $NORMALIZED_RESPONSE );print'</pre>';

    }


    //
    //  Attempt connections fetch
    ///
    $CLIENT         = new GuzzleHttp\Client();

    $query_str      = 'https://discordapp.com/api/v6/users/@me/connections';

    $CONNECTIONS_RESPONSE = $CLIENT->request( 'GET', $query_str, [
        'headers' => [
            'Authorization' => 'Bearer ' . $PARSED_TOKEN_RESPONSE->access_token
        ],
    ] );


    //
    //  Parse and check for failure
    //
    $PARSED_CONNECTIONS_RESPONSE  = json_decode( $CONNECTIONS_RESPONSE->getBody()->getContents() );

    if( !empty( $CONTAINER['SETTINGS']->debug ) && $CONTAINER['SETTINGS']->visitor == $_SERVER['REMOTE_ADDR'] ) {

        print'<pre>';print_r( $PARSED_CONNECTIONS_RESPONSE );print'</pre>';

    }

    if( !empty( $PARSED_CONNECTIONS_RESPONSE ) ) {

        $CONTAINER['logger']->addInfo( serialize( json_encode( $PARSED_CONNECTIONS_RESPONSE ) ) );

    }


    //
    //  Attempt guilds fetch
    //
    $CLIENT         = new GuzzleHttp\Client();

    $query_str      = 'https://discordapp.com/api/v6/users/@me/guilds';

    $GUILDS_RESPONSE = $CLIENT->request( 'GET', $query_str, [
        'headers' => [
            'Authorization' => 'Bearer ' . $PARSED_TOKEN_RESPONSE->access_token
        ],
    ] );


    //
    //  Parse and check for failure
    //
    $PARSED_GUILDS_RESPONSE  = json_decode( $GUILDS_RESPONSE->getBody()->getContents() );

    if( !empty( $CONTAINER['SETTINGS']->debug ) && $CONTAINER['SETTINGS']->visitor == $_SERVER['REMOTE_ADDR'] ) {

        print'<pre>';print_r( $PARSED_GUILDS_RESPONSE );print'</pre>';

    }

    if( !empty( $PARSED_GUILDS_RESPONSE ) ) {

        $CONTAINER['logger']->addInfo( serialize( json_encode( $PARSED_GUILDS_RESPONSE ) ) );

    }


    //
    //  Attempt invite fetch
    //
    $CLIENT         = new GuzzleHttp\Client();

    $query_str      = 'https://discordapp.com/api/v6/invites/' . $CONTAINER['SETTINGS']->social[0]->discord[0]->invite;

    $INVITE_RESPONSE = $CLIENT->request( 'GET', $query_str, [
        'headers' => [
            'Authorization' => 'Bearer ' . $PARSED_TOKEN_RESPONSE->access_token
        ],
    ] );


    //
    //  Parse and check for failure
    //
    $PARSED_INVITE_RESPONSE  = json_decode( $INVITE_RESPONSE->getBody()->getContents() );

    if( !empty( $CONTAINER['SETTINGS']->debug ) && $CONTAINER['SETTINGS']->visitor == $_SERVER['REMOTE_ADDR'] ) {

        print'<pre>';print_r($PARSED_INVITE_RESPONSE);print'</pre>';

    }

    if( empty( $PARSED_INVITE_RESPONSE->code ) ) {

        $REDIRECT_URL   = !empty( $CONTAINER['SETTINGS']->using_https ) ? 'https://' : 'http://' . $CONTAINER['SETTINGS']->parent_domain . '/signup.php?';

        $REDIRECT_DATA  = [
            'stat'      => false,
            'message'   => 'Sorry about this but we are unable to get you signed up through Discord at this time',
        ];

        $REDIRECT_URL   .= http_build_query( $REDIRECT_DATA );

        header( 'Location: ' . $REDIRECT_URL );

    }


    //
    //  Attempt add user
    //
    $CLIENT         = new GuzzleHttp\Client();

    $query_str      = 'https://discordapp.com/api/v6/invites/' . $CONTAINER['SETTINGS']->social[0]->discord[0]->invite;

    $INSERT_RESPONSE = $CLIENT->request( 'POST', $query_str, [
        'headers' => [
            'Authorization' => 'Bearer ' . $PARSED_TOKEN_RESPONSE->access_token
        ],
    ] );


    //
    //  Parse and check for failure
    //
    $PARSED_INSERT_RESPONSE  = json_decode( $INSERT_RESPONSE->getBody()->getContents() );

    if( !empty( $CONTAINER['SETTINGS']->debug ) && $CONTAINER['SETTINGS']->visitor == $_SERVER['REMOTE_ADDR'] ) {

        print'<pre>';print_r($PARSED_INSERT_RESPONSE);print'</pre>';

    }

    if( empty( $PARSED_INSERT_RESPONSE ) ) {

        $REDIRECT_URL   = !empty( $CONTAINER['SETTINGS']->using_https ) ? 'https://' : 'http://' . $CONTAINER['SETTINGS']->parent_domain . '/signup.php?';

        $REDIRECT_DATA  = [
            'stat'      => false,
            'message'   => 'Sorry about this but we are unable to get you signed up through Discord at this time',
        ];

        $REDIRECT_URL   .= http_build_query( $REDIRECT_DATA );

        header( 'Location: ' . $REDIRECT_URL );

    }

    $CONTAINER['logger']->addInfo( serialize( json_encode( $PARSED_INSERT_RESPONSE ) ) );

    $REDIRECT_URL   = !empty( $CONTAINER['SETTINGS']->using_https ) ? 'https://' : 'http://' . $CONTAINER['SETTINGS']->parent_domain . '/signup.php?';

    $REDIRECT_DATA  = [
        'stat'      => true,
        'message'   => 'Hooray ' . $PARSED_USER_RESPONSE->username . '! You\'ve successfully signed up!',
    ];

    $REDIRECT_URL   .= http_build_query( $REDIRECT_DATA );

    header( 'Location: ' . $REDIRECT_URL );

} );


$APP->get('/callback', function( ServerRequestInterface $REQUEST, ResponseInterface $RESPONSE ) use( $CONTAINER ) {

    $CONTAINER['logger']->addInfo( serialize( $REQUEST ) );

} );


//
//  Discord auth
//  http://api.likely.cloud/discord
//
$APP->get('/discord', function( ServerRequestInterface $REQUEST, ResponseInterface $RESPONSE ) use( $CONTAINER ) {

    $REDIRECT_URL   = 'https://discordapp.com/api/v6/oauth2/authorize?response_type=code&client_id=' . $CONTAINER['SETTINGS']->social[0]->discord[0]->client_id
        . '&scope=' . $CONTAINER['SETTINGS']->social[0]->discord[0]->scope
        . '&redirect_uri=' . ( !empty( $CONTAINER['SETTINGS']->using_https ) ? 'https://' : 'http://' ) . $CONTAINER['SETTINGS']->domain . '/' . $CONTAINER['SETTINGS']->social[0]->discord[0]->callback;

    header( 'Location: ' . $REDIRECT_URL );

} );


$APP->run();