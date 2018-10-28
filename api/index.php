<?php
//
//  Session initialization
//
ini_set( 'session.auto_start', true );
session_save_path('/tmp' );
session_start();

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
//  Tumblr authentication
//
$APP->get('/tumblr_auth', function( ServerRequestInterface $REQUEST, ResponseInterface $RESPONSE ) use( $CONTAINER ) {

    $PARSED_REQUEST = $REQUEST->getQueryParams();

    if( !empty( $CONTAINER['SETTINGS']->debug ) && $CONTAINER['SETTINGS']->visitor == $_SERVER['REMOTE_ADDR'] ) {

        print'<pre>';print_r( $PARSED_REQUEST );print'</pre>';

    }

    $client = new Tumblr\API\Client(
        $CONTAINER['SETTINGS']->social[0]->tumblr[0]->server[0]->client_id,
        $CONTAINER['SETTINGS']->social[0]->tumblr[0]->server[0]->client_secret,
        $PARSED_REQUEST['oauth_token'], $_SESSION['oauth_token_secret']
    );

    $requestHandler = $client->getRequestHandler();

    $requestHandler->setBaseUrl( 'https://www.tumblr.com/' );

    $response = $requestHandler->request('POST', 'oauth/access_token', [
        'oauth_verifier' => $PARSED_REQUEST['oauth_verifier'],
    ]);

    if( !empty( $CONTAINER['SETTINGS']->debug ) && $CONTAINER['SETTINGS']->visitor == $_SERVER['REMOTE_ADDR'] ) {

        var_dump('response_status', $response->status );

    }

    parse_str( (string) $response->body, $PARSED_REQUEST_TOKENS_RESPONSE );

    if( !empty( $CONTAINER['SETTINGS']->debug ) && $CONTAINER['SETTINGS']->visitor == $_SERVER['REMOTE_ADDR'] ) {

        print'<pre>';print_r( $PARSED_REQUEST_TOKENS_RESPONSE );print'</pre>';

    }

    $client = new Tumblr\API\Client(
        $CONTAINER['SETTINGS']->social[0]->tumblr[0]->server[0]->client_id,
        $CONTAINER['SETTINGS']->social[0]->tumblr[0]->server[0]->client_secret,
        $PARSED_REQUEST_TOKENS_RESPONSE['oauth_token'],
        $PARSED_REQUEST_TOKENS_RESPONSE['oauth_token_secret']
    );

    $PARSED_USER_RESPONSE  = $client->getUserInfo();


    //
    //  check if already authenticated
    //
    $username       = !empty( $_SESSION['username'] ) ? $_SESSION['username'] : null;

    $authenticated  = !empty( $_SESSION['authenticated'] ) ? $_SESSION['authenticated'] : null;

    if( !empty( $username) and !empty( $authenticated ) ) {

        $REDIRECT_URL   = ( !empty( $CONTAINER['SETTINGS']->using_https ) ? 'https://' : 'http://' ) . $CONTAINER['SETTINGS']->parent_domain . '/signup.php?';

        $REDIRECT_DATA  = [
            'stat'      => true,
            'message'   => 'Yay ' . $username . '! Welcome back!',
        ];

        $REDIRECT_URL   .= http_build_query( $REDIRECT_DATA );

        header( 'Location: ' . $REDIRECT_URL );

        return $REDIRECT_DATA;

    }


    //
    //  Normalize
    //
    $NORMALIZED_RESPONSE                    = [];

    $NORMALIZED_RESPONSE['tumblr_registry'] = $PARSED_USER_RESPONSE;

    $_SESSION['username']                   = $PARSED_USER_RESPONSE->user->name;

    $_SESSION['authenticated']              = true;

    /*
    if( !empty( $PARSED_USER_RESPONSE->user->blogs[0]->url ) ) {

        //
        //  Attempt avatar parse
        //
        $SEARCH_RESPONSE    = file_get_contents( $PARSED_USER_RESPONSE->user->blogs[0]->url );

        $DOM                = new DOMDocument;

        $DOM->loadHTML( $SEARCH_RESPONSE );

        $XPATH              = new DOMXPath( $DOM );

        $SEARCH_RESULTS     = $XPATH->query( '//a[@class="user-avatar"]' );

        if( !empty( $SEARCH_RESULTS->length ) ) {

            foreach( $SEARCH_RESULTS as $RESULT ) {

                var_dump( get_class_methods( $RESULT) );

                break;

            }


        }

        #$NORMALIZED_RESPONSE['discord_registry']->avatar  = 'https://discordapp.com/api/v6/users/' . $PARSED_USER_RESPONSE->id . '/avatars/' . $PARSED_USER_RESPONSE->avatar . '.jpg';

    }
    */

    $CONTAINER['logger']->addInfo( serialize( $NORMALIZED_RESPONSE ) );

} );


//
//  Tumblr redirect
//
$APP->get('/tumblr', function( ServerRequestInterface $REQUEST, ResponseInterface $RESPONSE ) use( $CONTAINER ) {

    $client = new Tumblr\API\Client(
        $CONTAINER['SETTINGS']->social[0]->tumblr[0]->server[0]->client_id,
        $CONTAINER['SETTINGS']->social[0]->tumblr[0]->server[0]->client_secret
    );

    $requestHandler = $client->getRequestHandler();

    $requestHandler->setBaseUrl( 'https://www.tumblr.com/' );

    $resp = $requestHandler->request( 'POST', 'oauth/request_token', [
        'oauth_callback' => ( !empty( $CONTAINER['SETTINGS']->using_https ) ? 'https://' : 'http://' )
            . $CONTAINER['SETTINGS']->domain . '/' . $CONTAINER['SETTINGS']->social[0]->tumblr[0]->register[0]->callback,
    ] ) ;

    parse_str( $resp->body, $PARSED_TOKEN_RESPONSE );

    if( !empty( $CONTAINER['SETTINGS']->debug ) && $CONTAINER['SETTINGS']->visitor == $_SERVER['REMOTE_ADDR'] ) {

        print'<pre>';print_r( $PARSED_TOKEN_RESPONSE );print'</pre>';

    }

    $_SESSION['oauth_token']    = $PARSED_TOKEN_RESPONSE['oauth_token'];

    $_SESSION['oauth_token_secret'] = $PARSED_TOKEN_RESPONSE['oauth_token_secret'];

    header( 'Location: ' . 'https://www.tumblr.com/oauth/authorize?oauth_token=' . $PARSED_TOKEN_RESPONSE['oauth_token'] );

    return $RESPONSE;

});



//
//  Discord authentication
//
$APP->get('/discord_auth', function( ServerRequestInterface $REQUEST, ResponseInterface $RESPONSE ) use( $CONTAINER ) {

    $PARSED_REQUEST = $REQUEST->getQueryParams();

    if( !empty( $CONTAINER['SETTINGS']->debug ) && $CONTAINER['SETTINGS']->visitor == $_SERVER['REMOTE_ADDR'] ) {

        print'<pre>';print_r( $PARSED_REQUEST );print'</pre>';

    }


    //
    //  check if already authenticated
    //
    $username       = !empty( $_SESSION['username'] ) ? $_SESSION['username'] : null;

    $authenticated  = !empty( $_SESSION['authenticated'] ) ? $_SESSION['authenticated'] : null;

    if( !empty( $username) and !empty( $authenticated ) ) {

        $REDIRECT_URL   = ( !empty( $CONTAINER['SETTINGS']->using_https ) ? 'https://' : 'http://' ) . $CONTAINER['SETTINGS']->parent_domain . '/signup.php?';

        $REDIRECT_DATA  = [
            'stat'      => true,
            'message'   => 'Yay ' . $username . '! Welcome back!',
        ];

        $REDIRECT_URL   .= http_build_query( $REDIRECT_DATA );

        header( 'Location: ' . $REDIRECT_URL );

        return $REDIRECT_DATA;

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
            'client_id'         => $CONTAINER['SETTINGS']->social[0]->discord[0]->server[0]->client_id,
            'client_secret'     => $CONTAINER['SETTINGS']->social[0]->discord[0]->server[0]->client_secret,
            'grant_type'        => 'authorization_code',
            'code'              => $PARSED_REQUEST['code'],
            'redirect_uri'      => ( !empty( $CONTAINER['SETTINGS']->using_https ) ? 'https://' : 'http://' ) . $CONTAINER['SETTINGS']->domain . '/' . $CONTAINER['SETTINGS']->social[0]->discord[0]->register[0]->callback,
            'scope'             => $CONTAINER['SETTINGS']->social[0]->discord[0]->register[0]->scope,
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

        $REDIRECT_URL   = ( !empty( $CONTAINER['SETTINGS']->using_https ) ? 'https://' : 'http://' ) . $CONTAINER['SETTINGS']->parent_domain . '/signup.php?';

        $REDIRECT_DATA  = [
            'stat'      => false,
            'message'   => 'Sorry about this but we are unable to get you signed up through Discord at this time',
        ];

        $REDIRECT_URL   .= http_build_query( $REDIRECT_DATA );

        header( 'Location: ' . $REDIRECT_URL );

        return $REDIRECT_DATA;

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

        $REDIRECT_URL   = ( !empty( $CONTAINER['SETTINGS']->using_https ) ? 'https://' : 'http://' ) . $CONTAINER['SETTINGS']->parent_domain . '/signup.php?';

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

        foreach( $PARSED_GUILDS_RESPONSE as $GUILD_RESPONSE ) {

            if( $GUILD_RESPONSE->name == $CONTAINER['SETTINGS']->social[0]->discord[0]->server[0]->name ) {

                $_SESSION['username']       = $PARSED_USER_RESPONSE->username;
                $_SESSION['authenticated']  = true;

                $REDIRECT_URL   = ( !empty( $CONTAINER['SETTINGS']->using_https ) ? 'https://' : 'http://' ) . $CONTAINER['SETTINGS']->parent_domain . '/signup.php?';

                $REDIRECT_DATA  = [
                    'stat'      => true,
                    'message'   => 'Yay ' . $PARSED_USER_RESPONSE->username . '! Welcome back!',
                ];

                $REDIRECT_URL   .= http_build_query( $REDIRECT_DATA );

                header( 'Location: ' . $REDIRECT_URL );

                return $REDIRECT_DATA;

            }

        }

        $CONTAINER['logger']->addInfo( serialize( json_encode( $PARSED_GUILDS_RESPONSE ) ) );

    }


    //
    //  Attempt invite fetch
    //
    $CLIENT         = new GuzzleHttp\Client();

    $query_str      = 'https://discordapp.com/api/v6/invites/' . $CONTAINER['SETTINGS']->social[0]->discord[0]->register[0]->invite;

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

        $REDIRECT_URL   = ( !empty( $CONTAINER['SETTINGS']->using_https ) ? 'https://' : 'http://' ) . $CONTAINER['SETTINGS']->parent_domain . '/signup.php?';

        $REDIRECT_DATA  = [
            'stat'      => false,
            'message'   => 'Sorry about this but we are unable to get you signed up through Discord at this time',
        ];

        $REDIRECT_URL   .= http_build_query( $REDIRECT_DATA );

        header( 'Location: ' . $REDIRECT_URL );

        return $REDIRECT_DATA;

    }


    //
    //  Attempt add user
    //
    $CLIENT         = new GuzzleHttp\Client();

    $query_str      = 'https://discordapp.com/api/v6/invites/' . $CONTAINER['SETTINGS']->social[0]->discord[0]->register[0]->invite;

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

        $REDIRECT_URL   = ( !empty( $CONTAINER['SETTINGS']->using_https ) ? 'https://' : 'http://' ) . $CONTAINER['SETTINGS']->parent_domain . '/signup.php?';

        $REDIRECT_DATA  = [
            'stat'      => false,
            'message'   => 'Sorry about this but we are unable to get you signed up through Discord at this time',
        ];

        $REDIRECT_URL   .= http_build_query( $REDIRECT_DATA );

        header( 'Location: ' . $REDIRECT_URL );

        return $REDIRECT_DATA;

    }

    $CONTAINER['logger']->addInfo( serialize( json_encode( $PARSED_INSERT_RESPONSE ) ) );

    $REDIRECT_URL   = ( !empty( $CONTAINER['SETTINGS']->using_https ) ? 'https://' : 'http://' ) . $CONTAINER['SETTINGS']->parent_domain . '/signup.php?';

    $REDIRECT_DATA  = [
        'stat'      => true,
        'message'   => 'Hooray ' . $PARSED_USER_RESPONSE->username . '! You\'ve successfully signed up!',
    ];

    $REDIRECT_URL   .= http_build_query( $REDIRECT_DATA );

    header( 'Location: ' . $REDIRECT_URL );

    return $REDIRECT_DATA;

} );


//
//  Discord redirect
//
$APP->get('/discord', function( ServerRequestInterface $REQUEST, ResponseInterface $RESPONSE ) use( $CONTAINER ) {

    //
    //  check if already authed
    //
    $username       = $_SESSION['username'];

    $authenticated  = $_SESSION['authenticated'];

    if( !empty( $username) and !empty( $authenticated ) ) {

        $REDIRECT_URL   = ( !empty( $CONTAINER['SETTINGS']->using_https ) ? 'https://' : 'http://' ) . $CONTAINER['SETTINGS']->parent_domain . '/signup.php?';

        $REDIRECT_DATA  = [
            'stat'      => true,
            'message'   => 'Yay ' . $username . '! Welcome back!',
        ];

        $REDIRECT_URL   .= http_build_query( $REDIRECT_DATA );

        header( 'Location: ' . $REDIRECT_URL );

        return $REDIRECT_DATA;

    }

    $REDIRECT_URL   = 'https://discordapp.com/api/v6/oauth2/authorize?response_type=code&client_id=' . $CONTAINER['SETTINGS']->social[0]->discord[0]->server[0]->client_id
        . '&scope=' . $CONTAINER['SETTINGS']->social[0]->discord[0]->register[0]->scope
        . '&redirect_uri=' . ( !empty( $CONTAINER['SETTINGS']->using_https ) ? 'https://' : 'http://' ) . $CONTAINER['SETTINGS']->domain . '/' . $CONTAINER['SETTINGS']->social[0]->discord[0]->register[0]->callback;

    header( 'Location: ' . $REDIRECT_URL );

    return $RESPONSE;

} );


//
//  Facebook auth
//  https://developers.facebook.com/docs/facebook-login/manually-build-a-login-flow/
//
$APP->get('/facebook', function( ServerRequestInterface $REQUEST, ResponseInterface $RESPONSE ) use( $CONTAINER ) {

    $REDIRECT_URL   = 'https://www.facebook.com/v3.1/dialog/oauth?client_id=' . $CONTAINER['SETTINGS']->social[0]->facebook[0]->client_id
        . '&state=' . $CONTAINER['SETTINGS']->session[0]->name . '_' . rand( 0, time() )
        . '&redirect_uri=' . ( !empty( $CONTAINER['SETTINGS']->using_https ) ? 'https://' : 'http://' ) . $CONTAINER['SETTINGS']->domain . '/' . $CONTAINER['SETTINGS']->social[0]->facebook[0]->callback;

    header( 'Location: ' . $REDIRECT_URL );

    return $RESPONSE;

} );


//
//  Facebook auth
//
$APP->get('/facebook_auth', function( ServerRequestInterface $REQUEST, ResponseInterface $RESPONSE ) use( $CONTAINER ) {

    $PARSED_REQUEST = $REQUEST->getQueryParams();


    //
    //  Attempt token fetch
    //
    $CLIENT         = new GuzzleHttp\Client();

    $query_str      = 'https://graph.accountkit.com/' . $CONTAINER['SETTINGS']->social[0]->facebook[0]->version . '/access_token';

    if( !empty( $CONTAINER['SETTINGS']->debug ) && $CONTAINER['SETTINGS']->visitor == $_SERVER['REMOTE_ADDR'] ) {

        print'<pre>';print_r( [
            'grant_type'        => 'authorization_code',
            'code'              => $PARSED_REQUEST['code'],
            'access_token'      => 'AA|' . $CONTAINER['SETTINGS']->social[0]->facebook[0]->client_id . '|' . $CONTAINER['SETTINGS']->social[0]->facebook[0]->client_secret,
        ] );print'</pre>';

    }

    $TOKEN_RESPONSE = $CLIENT->request( 'GET', $query_str, [
        'query' => [
            'grant_type'        => 'authorization_code',
            'code'              => $PARSED_REQUEST['code'],
            'access_token'      => 'AA|' . $CONTAINER['SETTINGS']->social[0]->facebook[0]->client_id . '|' . $CONTAINER['SETTINGS']->social[0]->facebook[0]->client_secret,
        ],
    ] );

    $CONTAINER['logger']->addInfo( serialize( $TOKEN_RESPONSE ) );

    return json_encode( array( 'stat' => true, 'message' => 'Not integrated yet' ) );

} );


//
//  IRC search
//
$APP->get('/ircsearch/{q}', function( ServerRequestInterface $REQUEST, ResponseInterface $RESPONSE ) use( $CONTAINER ) {

    $PARSED_REQUEST = $REQUEST->getAttribute('q');

    $CONTAINER['logger']->addInfo( serialize( $PARSED_REQUEST ) );

    $RESPONSE  = [
        'stat'      => false,
        'message'   => array(
            'SERVERS'   => array(),
        ),
    ];


    //
    //  Attempt search
    //
    $CLIENT         = new GuzzleHttp\Client();

    $query_str      = 'https://search.mibbit.com';

    $SEARCH_RESPONSE = $CLIENT->request( 'GET', $query_str, [
        'query' => [
            'q' => $PARSED_REQUEST,
        ],
    ] );


    //
    //  Parse the search
    //
    $PARSED_RESPONSE    = $SEARCH_RESPONSE->getBody()->getContents();

    $DOM                = new DOMDocument;

    $DOM->loadHTML( $PARSED_RESPONSE );

    $XPATH              = new DOMXPath( $DOM );

    $pattern            = '
    /
    \{              # { character
        (?:         # non-capturing group
            [^{}]   # anything that is not a { or }
            |       # OR
            (?R)    # recurses the entire pattern
        )*          # previous group zero or more times
    \}              # } character
    /x
    ';

    $SEARCH_RESULTS     = $XPATH->query( '//div[@class="result"]//a[@class="connectlink"]' );

    if( empty( $SEARCH_RESULTS->length ) ) {

        $RESPONSE['message']    = 'Could not find any relevant hits';

        return json_encode( $RESPONSE );

    }

    foreach( $SEARCH_RESULTS as $RESULT ) {

        preg_match( $pattern, $RESULT->getAttribute( 'onclick'), $MATCHES );

        if( empty( $MATCHES or empty( $MATCHES[0] ) ) ) {

            $RESPONSE['message']    = 'Sorry, that is not expected';

            $CONTAINER['logger']->addInfo( serialize( [ 'Bad response on ' . __LINE__ . ' in ' . __FILE__ ] ) );

            return json_encode( $RESPONSE );

        }

        $JSON   = json_decode( $MATCHES[0] );

        if( strpos( $JSON->addr, ':' ) == false ) {

            $server_name = $JSON->addr . ':6667';

        } else {

            $server_name   = str_replace( ':+', ':', $JSON->addr );

        }

        $server  = 'irc://' . $server_name . '/' . str_replace( '#', '', strtolower( $JSON->channels ) );

        $RESPONSE['message']['SERVERS'][]   = $server;

    }

    $RESPONSE['stat']   = true;

    return json_encode( $RESPONSE, JSON_UNESCAPED_SLASHES );

} );


//
//  Root requests
//
$APP->get('/', function( ServerRequestInterface $REQUEST, ResponseInterface $RESPONSE ) use( $CONTAINER ) {

    header( 'Location: ' . ( !empty( $CONTAINER['SETTINGS']->using_https ) ? 'https://' : 'http://' ) . $CONTAINER['SETTINGS']->parent_domain );

    return $RESPONSE;

} );

$APP->run();