<?php
//
//  Session initialization
//
#session_set_cookie_params( 3600, '/', 'api.likely.cloud', true );
ini_set( 'session.entropy_file', '/dev/urandom' );
ini_set( 'session.entropy_length', '512' );
ini_set( 'session.auto_start', true );
#session_cache_limiter( 'private' );
#session_cache_expire( 60 );
session_save_path( '/tmp' );
session_start();


//
//  Get initial settings
//
require_once $_SERVER['DOCUMENT_ROOT'] . '/bootstrap.php';

if( empty( $SETTINGS ) ) die( 'Improperly configured ' . __FILE__ );

$SETTINGS->api_hash = 'Gbr363GBcULpP5RepWNCs9DWh6bmkuRt';


//
//  Determine API status
//
if( $SETTINGS->visitor != $_SERVER['REMOTE_ADDR'] ) {

    json_encode( ['stat' => false, 'message' => 'Check us out later!' ] );

}


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
//  Additional settings
//
$CONFIG['displayErrorDetails']      = $SETTINGS->debug;
$CONFIG['addContentLengthHeader']   = false;
$CONFIG['debug']                    = $SETTINGS->debug;


// Slim initialization
$APP = new \Slim\App( [
    'settings' => $CONFIG
] );


//
//  Plugin container
//
$CONTAINER = $APP->getContainer();


//
//  Database
//
$CONTAINER['DATABASE'] = function() use( $CONTAINER ) {

    $DATABASE = new PDO( "mysql:host=" . $CONTAINER['SETTINGS']->database[0]->mysql[0]->host . ";dbname=" . $CONTAINER['SETTINGS']->database[0]->mysql[0]->db_name, $CONTAINER['SETTINGS']->database[0]->mysql[0]->user_name, $CONTAINER['SETTINGS']->database[0]->mysql[0]->pass_word );

    $DATABASE->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );

    $DATABASE->setAttribute( PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC );

    return $DATABASE;
};


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
//  @todo: Double-check that Tumblr isn't capable of giving a refresh_token to store in user_data
//
$APP->get('/tumblr_auth', function( ServerRequestInterface $REQUEST, ResponseInterface $RESPONSE ) use( $CONTAINER ) {

    $PARSED_REQUEST = $REQUEST->getQueryParams();

    if( !empty( $CONTAINER['SETTINGS']->debug ) && $CONTAINER['SETTINGS']->visitor == $_SERVER['REMOTE_ADDR'] ) {

        print'<pre>';print_r( $PARSED_REQUEST );print'</pre>';

    }


    //
    //  Verify active service or quit
    //
    $DB         = new NotORM( $CONTAINER['DATABASE'] );

    $DB->debug  = $CONTAINER['SETTINGS']->debug;

    $SERVICE = iterator_to_array( $DB->services()->where( [
        'name'      => 'tumblr',
    ])->fetch() );

    if( empty( $SERVICE ) or $SERVICE['status'] != 'active' ) {

        $REDIRECT_URL   = ( !empty( $CONTAINER['SETTINGS']->using_https ) ? 'https://' : 'http://' ) . $CONTAINER['SETTINGS']->parent_domain . '/signup.php?';

        $REDIRECT_DATA  = [
            'stat'      => false,
            'message'   => 'Try again later',
        ];

        $REDIRECT_URL   .= http_build_query( $REDIRECT_DATA );

        header( 'Location: ' . $REDIRECT_URL );

        return $REDIRECT_DATA;

    }


    //
    //  Fetch API settings
    //
    $API_SETTINGS = iterator_to_array( $DB->settings()->fetch() );

    if( !empty( $CONTAINER['SETTINGS']->debug ) && $CONTAINER['SETTINGS']->visitor == $_SERVER['REMOTE_ADDR'] ) {

        print'<pre>';print_r( $API_SETTINGS );print'</pre>';

    }


    //
    //  Fetch transaction record where
    //  - service matches
    //  - was created within 15 minutes
    //
    $EXISTING_TRANSACTION   = $DB->transactions()->where( [
        'sid'                   => $SERVICE['id'],
        'session_id'            => session_id(),
    ] )->where( 'created >= "' . date( 'Y-m-d H:i:s', time() - ( 60 * 15 ) ) . '"' )->order( 'created desc' )->limit( 1 )->fetch();


    //
    //  Parse or send user back
    //
    if( empty( $EXISTING_TRANSACTION) ) {

        $REDIRECT_URL   = ( !empty( $CONTAINER['SETTINGS']->using_https ) ? 'https://' : 'http://' ) . $CONTAINER['SETTINGS']->parent_domain . '/signup.php?';

        $REDIRECT_DATA  = [
            'stat'      => false,
            'message'   => 'Try again later',
        ];

        $REDIRECT_URL   .= http_build_query( $REDIRECT_DATA );

        header( 'Location: ' . $REDIRECT_URL );

        return $REDIRECT_DATA;

    }

    $EXISTING_TRANSACTION   = iterator_to_array( $EXISTING_TRANSACTION );

    if( !empty( $CONTAINER['SETTINGS']->debug ) && $CONTAINER['SETTINGS']->visitor == $_SERVER['REMOTE_ADDR'] ) {

        print'<pre>';print_r( $EXISTING_TRANSACTION );print'</pre>';

    }


    //
    //  Parse the data
    //
    preg_match( '/^(.*)::(.*)$/', $EXISTING_TRANSACTION['oauth_token'], $PARSED_OAUTH_TOKEN );

    preg_match( '/^(.*)::(.*)$/', $EXISTING_TRANSACTION['oauth_token_secret'], $PARSED_OAUTH_TOKEN_SECRET );

    if( empty( $PARSED_OAUTH_TOKEN ) or empty( $PARSED_OAUTH_TOKEN_SECRET ) ) {

        $REDIRECT_URL   = ( !empty( $CONTAINER['SETTINGS']->using_https ) ? 'https://' : 'http://' ) . $CONTAINER['SETTINGS']->parent_domain . '/signup.php?';

        $REDIRECT_DATA  = [
            'stat'      => false,
            'message'   => 'Try again later',
        ];

        $REDIRECT_URL   .= http_build_query( $REDIRECT_DATA );

        header( 'Location: ' . $REDIRECT_URL );

        return $REDIRECT_DATA;

    }


    //
    //  Decrypt the data
    //
    $enc_method                                     = 'AES-128-CTR';

    list(, $encrypted_oauth_token, $enc_iv)         = $PARSED_OAUTH_TOKEN;

    $enc_key                                        = openssl_digest($CONTAINER['SETTINGS']->salt . ':' . $CONTAINER['SETTINGS']->api_hash . '|' . $API_SETTINGS['api_key'], 'SHA256', TRUE );

    $decrypted_oauth_token                          = openssl_decrypt( $encrypted_oauth_token, $enc_method, $enc_key, 0, hex2bin( $enc_iv ) );

    list(, $encrypted_oauth_token_secret, $enc_iv)  = $PARSED_OAUTH_TOKEN_SECRET;

    $decrypted_oauth_token_secret                   = openssl_decrypt( $encrypted_oauth_token_secret, $enc_method, $enc_key, 0, hex2bin( $enc_iv ) );


    //
    //  Confirm data validity
    //
    if( $decrypted_oauth_token != $PARSED_REQUEST['oauth_token'] ) {

        $REDIRECT_URL   = ( !empty( $CONTAINER['SETTINGS']->using_https ) ? 'https://' : 'http://' ) . $CONTAINER['SETTINGS']->parent_domain . '/signup.php?';

        $REDIRECT_DATA  = [
            'stat'      => false,
            'message'   => 'Try again later',
        ];

        $REDIRECT_URL   .= http_build_query( $REDIRECT_DATA );

        header( 'Location: ' . $REDIRECT_URL );

        return $REDIRECT_DATA;

    }


    //
    //  Update incoming vars
    //
    $PARSED_REQUEST['oauth_token_secret']   = $decrypted_oauth_token_secret;


    //
    //  Capture geo
    //
    $GEO = unserialize( file_get_contents( 'http://www.geoplugin.net/php.gp?ip=' . $_SERVER['REMOTE_ADDR'] ) );

    $PARSED_GEO_RESPONSE  = [
        'REMOTE_ADDR'       => $_SERVER['REMOTE_ADDR'],
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
    ];


    //
    //  Proceed with service auth
    //
    $client = new Tumblr\API\Client(
        $CONTAINER['SETTINGS']->social[0]->tumblr[0]->server[0]->client_id,
        $CONTAINER['SETTINGS']->social[0]->tumblr[0]->server[0]->client_secret,
        $PARSED_REQUEST['oauth_token'], $PARSED_REQUEST['oauth_token_secret']
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

    if( !empty( $CONTAINER['SETTINGS']->debug ) && $CONTAINER['SETTINGS']->visitor == $_SERVER['REMOTE_ADDR'] ) {

        print'<pre>';print_r( $PARSED_USER_RESPONSE );print'</pre>';

    }


    //
    //  Normalize
    //
    $NORMALIZED_RESPONSE                    = [];

    $NORMALIZED_RESPONSE['tumblr_registry'] = $PARSED_USER_RESPONSE;

    $_SESSION['username']                   = $PARSED_USER_RESPONSE->user->name;

    $_SESSION['authenticated']              = true;

    $CONTAINER['logger']->addInfo( serialize( $NORMALIZED_RESPONSE ) );


    //
    //  Fetch user
    //
    $EXISTING_USER = $DB->users()->where( [
        'uname'     => $PARSED_USER_RESPONSE->user->name,
    ] )->fetch();


    //
    //  Quit when found
    //
    if( !empty( $EXISTING_USER ) ) {

        $REDIRECT_URL   = ( !empty( $CONTAINER['SETTINGS']->using_https ) ? 'https://' : 'http://' ) . $CONTAINER['SETTINGS']->parent_domain . '/signup.php?';

        $REDIRECT_DATA  = [
            'stat'      => true,
            'message'   => 'Yay ' . $PARSED_USER_RESPONSE->user->name . '! Welcome back!',
        ];

        $REDIRECT_URL   .= http_build_query( $REDIRECT_DATA );

        header( 'Location: ' . $REDIRECT_URL );

        return $REDIRECT_DATA;

    }


    //
    //  Append user
    //
    $DB->users()->insert( [
        'uname'     => $PARSED_USER_RESPONSE->user->name,
        'login'     => date( 'Y-m-d H:i:s' ),
        'status'    => 'active',
    ] );

    $user_id        = $DB->users()->insert_id();

    $DB->user_data()->insert_update(
        [
            'uid'       => $user_id,
        ],
        [
            'uid'       => $user_id,
            'geo'       => json_encode( $PARSED_GEO_RESPONSE ),
        ]
    );


    //
    //  Activate service for user
    //
    $DB->user_services()->insert_update(
        [
            'uid'       => $user_id,
            'sid'       => $SERVICE['id'],
        ],
        [
            'uid'       => $user_id,
            'sid'       => $SERVICE['id'],
            'activated' => date( 'Y-m-d H:i:s' ),
            'status'    => 'active',
            'refresh'   => $PARSED_REQUEST_TOKENS_RESPONSE['refresh_token'],
        ]
    );


    $REDIRECT_URL   = ( !empty( $CONTAINER['SETTINGS']->using_https ) ? 'https://' : 'http://' ) . $CONTAINER['SETTINGS']->parent_domain . '/signup.php?';

    $REDIRECT_DATA  = [
        'stat'      => true,
        'message'   => 'Yay ' . $PARSED_USER_RESPONSE->user->name . '! Welcome back!',
    ];

    $REDIRECT_URL   .= http_build_query( $REDIRECT_DATA );

    header( 'Location: ' . $REDIRECT_URL );

    return $REDIRECT_DATA;

} );


//
//  Tumblr redirect
//
$APP->get('/tumblr', function( ServerRequestInterface $REQUEST, ResponseInterface $RESPONSE ) use( $CONTAINER ) {

    //
    //  Verify active service or quit
    //
    $DB         = new NotORM( $CONTAINER['DATABASE'] );

    $DB->debug  = $CONTAINER['SETTINGS']->debug;

    $SERVICE = iterator_to_array( $DB->services()->where( [
        'name'      => 'tumblr',
    ])->fetch() );

    if( empty( $SERVICE ) or $SERVICE['status'] != 'active' ) {

        $REDIRECT_URL   = ( !empty( $CONTAINER['SETTINGS']->using_https ) ? 'https://' : 'http://' ) . $CONTAINER['SETTINGS']->parent_domain . '/signup.php?';

        $REDIRECT_DATA  = [
            'stat'      => false,
            'message'   => 'Try again later',
        ];

        $REDIRECT_URL   .= http_build_query( $REDIRECT_DATA );

        header( 'Location: ' . $REDIRECT_URL );

        return $REDIRECT_DATA;

    }


    //
    //  Get the user's permission
    //
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


    //
    //  Fetch API settings
    //
    $API_SETTINGS = iterator_to_array( $DB->settings()->fetch() );


    //
    //  Encrypt the data
    //
    $enc_method                     = 'AES-128-CTR';

    $enc_key                        = openssl_digest($CONTAINER['SETTINGS']->salt . ':' . $CONTAINER['SETTINGS']->api_hash . '|' . $API_SETTINGS['api_key'], 'SHA256', TRUE );

    $enc_iv                         = openssl_random_pseudo_bytes( openssl_cipher_iv_length( $enc_method ) );

    $encrypted_oauth_token          = openssl_encrypt( $PARSED_TOKEN_RESPONSE['oauth_token'], $enc_method, $enc_key, 0, $enc_iv ) . '::' . bin2hex($enc_iv);

    $encrypted_oauth_token_secret   = openssl_encrypt( $PARSED_TOKEN_RESPONSE['oauth_token_secret'], $enc_method, $enc_key, 0, $enc_iv ) . '::' . bin2hex($enc_iv);


    //
    //  Append transaction
    //
    $DB->transactions()->insert( [
        'sid'                   => $SERVICE['id'],
        'session_id'            => session_id(),
        'oauth_token'           => $encrypted_oauth_token,
        'oauth_token_secret'    => $encrypted_oauth_token_secret,
        'created'               => date( 'Y-m-d H:i:s' ),
    ] );

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
        . '&state=' . md5( $CONTAINER['SETTINGS']->session[0]->name . '_' . $CONTAINER['SETTINGS']->salt )
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
//  imgur redirect
//
$APP->get('/imgur', function( ServerRequestInterface $REQUEST, ResponseInterface $RESPONSE ) use( $CONTAINER ) {

    //
    //  check if already authed
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

    $REDIRECT_URL   = 'https://api.imgur.com/oauth2/authorize?response_type=code&client_id=' . $CONTAINER['SETTINGS']->social[0]->imgur[0]->server[0]->client_id
        . '&state=' . md5( $CONTAINER['SETTINGS']->session[0]->name . '_' . $CONTAINER['SETTINGS']->salt );

    header( 'Location: ' . $REDIRECT_URL );

    return $RESPONSE;

} );


//
//  imgur auth
//
$APP->get('/imgur_auth', function( ServerRequestInterface $REQUEST, ResponseInterface $RESPONSE ) use( $CONTAINER ) {

    $PARSED_REQUEST = $REQUEST->getQueryParams();

    if( !empty( $CONTAINER['SETTINGS']->debug ) && $CONTAINER['SETTINGS']->visitor == $_SERVER['REMOTE_ADDR'] ) {

        print'<pre>';print_r( $PARSED_REQUEST );print'</pre>';

    }


    //
    //  Verify the request came from us
    //

    if( $PARSED_REQUEST['state'] != md5( $CONTAINER['SETTINGS']->session[0]->name . '_' . $CONTAINER['SETTINGS']->salt ) ) {

        return $RESPONSE;

    }


    //
    //  Attempt token fetch
    //
    $CLIENT     = new GuzzleHttp\Client();

    $query_str = 'https://api.imgur.com/oauth2/token';

    $TOKEN_RESPONSE = $CLIENT->request( 'POST', $query_str, [
        'headers' => [
            'content-type: application/x-www-form-urlencoded',
        ],
        'form_params' => [
            'client_id'         => $CONTAINER['SETTINGS']->social[0]->imgur[0]->server[0]->client_id,
            'client_secret'     => $CONTAINER['SETTINGS']->social[0]->imgur[0]->server[0]->client_secret,
            'grant_type'        => 'authorization_code',
            'code'              => $PARSED_REQUEST['code'],
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
            'message'   => 'Sorry about this but we are unable to get you signed up through imgur at this time',
        ];

        $REDIRECT_URL   .= http_build_query( $REDIRECT_DATA );

        header( 'Location: ' . $REDIRECT_URL );

        return $REDIRECT_DATA;

    }


    //
    //  Attempt user fetch
    //
    $CLIENT                 = new GuzzleHttp\Client();

    $query_str              = 'https://api.imgur.com/3/account/' . $PARSED_TOKEN_RESPONSE->account_username;

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

    if( empty( $PARSED_USER_RESPONSE->data ) ) {

        $REDIRECT_URL   = ( !empty( $CONTAINER['SETTINGS']->using_https ) ? 'https://' : 'http://' ) . $CONTAINER['SETTINGS']->parent_domain . '/signup.php?';

        $REDIRECT_DATA  = [
            'stat'      => false,
            'message'   => 'Sorry about this but we are unable to get you signed up through imgur at this time',
        ];

        $REDIRECT_URL   .= http_build_query( $REDIRECT_DATA );

        header( 'Location: ' . $REDIRECT_URL );
    }


    //
    //  Normalize
    //
    $NORMALIZED_RESPONSE                        = [];

    $NORMALIZED_RESPONSE['imgur_registry']      = $PARSED_USER_RESPONSE;

    if( !empty( $CONTAINER['SETTINGS']->debug ) && $CONTAINER['SETTINGS']->visitor == $_SERVER['REMOTE_ADDR'] ) {

        print'<pre>';print_r( $NORMALIZED_RESPONSE );print'</pre>';

    }

    $CONTAINER['logger']->addInfo( serialize( json_encode( $NORMALIZED_RESPONSE ) ) );

    $REDIRECT_URL   = ( !empty( $CONTAINER['SETTINGS']->using_https ) ? 'https://' : 'http://' ) . $CONTAINER['SETTINGS']->parent_domain . '/signup.php?';

    $REDIRECT_DATA  = [
        'stat'      => true,
        'message'   => 'Hooray ' . $PARSED_TOKEN_RESPONSE->account_username. '! You\'ve successfully signed up!',
    ];

    $REDIRECT_URL   .= http_build_query( $REDIRECT_DATA );

    header( 'Location: ' . $REDIRECT_URL );

    return $REDIRECT_DATA;

} );


//
//  imgur upload
//
$APP->get('/imgur_upload', function( ServerRequestInterface $REQUEST, ResponseInterface $RESPONSE ) use( $CONTAINER ) {

    $PARSED_REQUEST = $REQUEST->getQueryParams();

    if( !empty( $CONTAINER['SETTINGS']->debug ) && $CONTAINER['SETTINGS']->visitor == $_SERVER['REMOTE_ADDR'] ) {

        print'<pre>';print_r( $PARSED_REQUEST );print'</pre>';

    }

} );


//
//  imgur edit
//
$APP->get('/imgur_edit', function( ServerRequestInterface $REQUEST, ResponseInterface $RESPONSE ) use( $CONTAINER ) {

    $PARSED_REQUEST = $REQUEST->getQueryParams();

    if( !empty( $CONTAINER['SETTINGS']->debug ) && $CONTAINER['SETTINGS']->visitor == $_SERVER['REMOTE_ADDR'] ) {

        print'<pre>';print_r( $PARSED_REQUEST );print'</pre>';

    }

} );


//
//  imgur delete
//
$APP->get('/imgur_delete', function( ServerRequestInterface $REQUEST, ResponseInterface $RESPONSE ) use( $CONTAINER ) {

    $PARSED_REQUEST = $REQUEST->getQueryParams();

    if( !empty( $CONTAINER['SETTINGS']->debug ) && $CONTAINER['SETTINGS']->visitor == $_SERVER['REMOTE_ADDR'] ) {

        print'<pre>';print_r( $PARSED_REQUEST );print'</pre>';

    }

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

    @$DOM->loadHTML( $PARSED_RESPONSE );

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