<?php
//
//  Get initial settings
//
require_once $_SERVER['DOCUMENT_ROOT'] . '/bootstrap.php';

if( empty( $SETTINGS ) ) die( 'Improperly configured ' . __FILE__ );

$SETTINGS->api_hash = 'asdf';


//
//  Determine API status
//
if( $SETTINGS->visitor != $_SERVER['REMOTE_ADDR'] ) {

    json_encode( ['stat' => false, 'message' => 'Check us out later!' ] );

}


//
//  Headers
//
#header( 'Access-Control-Allow-Origin: *' );
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


//  Slim initialization
$APP = new \Slim\App( [
    'settings' => $CONFIG
] );


//
//  Session start
//  @todo: session keeps creating files on disk
//  - the helper is only getters and setters
//  - the session is created via new \Slim\Middleware\Session
//  - why does it keep creating new files?
//  - Try moving the middleware and the helper instantiation into a loadable class and then call it in each route/verify session persists
//  - or try moving the middleware and helper into each route/verify session persists
//
if( session_status() === PHP_SESSION_NONE ) {

    $APP->add( new \Slim\Middleware\Session( [
      'name'            => $SETTINGS->session[0]->name,
      'path'            => $SETTINGS->session[0]->path,
      'lifetime'        => $SETTINGS->session[0]->lifetime,
      'autorefresh'     => $SETTINGS->session[0]->autorefresh,
      'domain'          => $SETTINGS->session[0]->domain,
      'secure'          => $SETTINGS->session[0]->secure,
      'ini_settings'    => $SETTINGS->session[0]->ini_settings,
    ] ) );

}


//
//  Plugin container
//
$CONTAINER = $APP->getContainer();


//
//  Append container settings
//
$CONTAINER['SETTINGS']  = $SETTINGS;


//
//  Append container logging
//
$CONTAINER['logger'] = function() use( $CONTAINER ) {

    $LOGGER = new \Monolog\Logger( $CONTAINER['SETTINGS']->log[0]->name );

    $FH = new \Monolog\Handler\StreamHandler( $_SERVER['DOCUMENT_ROOT'] . $CONTAINER['SETTINGS']->log[0]->endpoint );

    $LOGGER->pushHandler( $FH );

    return $LOGGER;

};


//
//  Append container db
//  https://clancats.io/hydrahon/master/sql-query-builder/select/basics
//
$CONTAINER['DB'] = function() use( $CONTAINER ) {

    $CONNECTION = new PDO( 
        'mysql:host=' . $CONTAINER['SETTINGS']->database[0]->mysql[0]->host . ";dbname=" . $CONTAINER['SETTINGS']->database[0]->mysql[0]->db_name, 
        $CONTAINER['SETTINGS']->database[0]->mysql[0]->user_name, 
        $CONTAINER['SETTINGS']->database[0]->mysql[0]->pass_word, 
        [
            PDO::ATTR_PERSISTENT    => $CONTAINER['SETTINGS']->database[0]->persist,
            PDO::ATTR_ERRMODE       => !empty( $CONTAINER['SETTINGS']->debug ) ? PDO::ERRMODE_WARNING : PDO::ERRMODE_EXCEPTION,
        ] 
    );

    $DATABASE = new \ClanCats\Hydrahon\Builder( 'mysql', function( $query, $queryString, $queryParameters ) use( $CONNECTION ) {

        if( !empty( $CONTAINER['SETTINGS']->debug ) ) {

            $CONTAINER['logger']->addInfo( serialize( $queryString ) );

        }

        $statement = $CONNECTION->prepare( $queryString );

        $statement->execute( $queryParameters );

        if ( $query instanceof \ClanCats\Hydrahon\Query\Sql\FetchableInterface ) {

            return $statement->fetchAll( \PDO::FETCH_BOTH );

        }

    } );

    $DATABASE->c    = $CONNECTION;

    return $DATABASE;
};


//
//  Append container session
//
$CONTAINER['session'] = function() {

  return new \SlimSession\Helper;

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

    if( !empty( $CONTAINER['SETTINGS']->debug ) ) {

        $CONTAINER['logger']->addInfo( serialize( $PARSED_REQUEST ) );

    }

} );


$APP->get('/feed', function( ServerRequestInterface $REQUEST, ResponseInterface $RESPONSE ) use( $CONTAINER ) {

    $PARSED_REQUEST = $REQUEST->getQueryParams();

    if( !empty( $CONTAINER['SETTINGS']->debug ) and !empty( $PARSED_REQUEST ) ) {

        $CONTAINER['logger']->addInfo( serialize( $PARSED_REQUEST ) );

    }

    $CONTAINER['logger']->addInfo( serialize( $_SESSION ) );

    $CONTAINER['session']->set( 'test', 'something' );

    $CONTAINER['logger']->addInfo( serialize( $CONTAINER['session']->get( 'test' ) ) );

    $FEED_DATA  = [
        'USER_REGISTRIES'   => [],
    ];


    //
    //  Fetch user feed
    //
    $EXISTING_USERS  = $CONTAINER['DB']->table( 'users as u' )
    ->select( [
        'u.id', 
        'u.uname', 
        'u.created', 
        'ud.geo', 
        'ud.status', 
        'us.sname',
    ] )->join( 'user_data as ud', 'u.id', '=', 'ud.uid' )
        ->join( 'user_services as us', 'ud.uid', '=', 'us.uid' )
        ->join( 'services as s', 'us.sid', '=', 's.id' )
    ->where( 'ud.status', 'active' )
    ->orderBy( 'u.created', 'desc' )
    ->limit( 200 )
    ->get();

    foreach( $EXISTING_USERS as $USER_FEED ) {

        $FEED_DATA['USER_REGISTRIES'][ $USER_FEED['id'] ]['string_data'] = $USER_FEED['uname'] . ' signed up from ';

        $GEO = json_decode($USER_FEED['geo']);

        if ( !empty( $GEO->state ) ) {

            $FEED_DATA['USER_REGISTRIES'][ $USER_FEED['id'] ]['string_data'] .= $GEO->state . ', all the way out in  ';

        } else {

            $FEED_DATA['USER_REGISTRIES'][ $USER_FEED['id'] ]['string_data'] .= 'undisclosed location, all the way out in ';

        }

        if( !empty( $GEO->country_name ) ) {

            $FEED_DATA['USER_REGISTRIES'][ $USER_FEED['id'] ]['string_data'] .= $GEO->country_name . '! ';

        } else {

            $FEED_DATA['USER_REGISTRIES'][ $USER_FEED['id'] ]['string_data'] .= 'Nowhere\'sville! ';

        }

        $FEED_DATA['USER_REGISTRIES'][ $USER_FEED['id'] ]['string_data'] .= 'Decidedly using <' . $USER_FEED['sname'] . '> in ';

        $FEED_DATA['USER_REGISTRIES'][ $USER_FEED['id'] ]['string_data'] .= date( 'F', strtotime( $USER_FEED['created'] ) );

    }

    $DUMMY_FEED = [
        'Syn2k signed up from undisclosed location, all the way out in Nowhere\'sville! Decidedly using Discord <Syn2k@23453>',
        'jamison signed up from Nevada, all the way out in United States! Decidedly using imgur <@jamison>',
        'fahad signed up from California, all the way out in United States! Decidedly using email <withheld',
    ];

    $i = 999999999999;

    foreach( $DUMMY_FEED as $dummy ) {

        $FEED_DATA['USER_REGISTRIES'][ $i ]['string_data']  = $dummy;

        $i++;
    }

    return json_encode( ['stat' => true, 'message' => $FEED_DATA['USER_REGISTRIES'] ] );

} );


//
//  Register redirect
//
$APP->post('/register', function( ServerRequestInterface $REQUEST, ResponseInterface $RESPONSE ) use( $APP, $CONTAINER ) {

    $PARSED_REQUEST = $REQUEST->getParsedBody();


    //
    //  Verify active service or quit
    //
    $SERVICE  = $CONTAINER['DB']->table( 'services' )
    ->select()
    ->where( 'status', 'active' )
    ->where( 'name', 'register' )->one();

    if( empty( $SERVICE ) or $SERVICE['status'] != 'active' ) {

        if( !empty( $CONTAINER['SETTINGS']->debug ) ) {

            $CONTAINER['logger']->addInfo( serialize( [ 'access disabled service', __FILE__, __LINE__ ] ) );

        }

        return json_encode( [ 'stat' => false, 'redirect' => '/', 'message' => 'Try again later' ] );

    }


    //
    //  Fetch API settings or quit
    //
    $API_SETTINGS  = $CONTAINER['DB']->table( 'settings' )
    ->select()
    ->where( 'name', 'api_key' )->one();

    if( empty( $API_SETTINGS ) ) {

        if( !empty( $CONTAINER['SETTINGS']->debug ) ) {

            $CONTAINER['logger']->addInfo( serialize( [ 'something is broken', __FILE__, __LINE__ ] ) );

        }

        return json_encode( [ 'stat' => false, 'redirect' => '/', 'message' => 'Try again later' ] );

    }


    //
    //  Fetch user
    //
    $EXISTING_USER  = $CONTAINER['DB']->table( 'users' )
    ->select( ['id'] )
    ->where( 'uname', $PARSED_REQUEST['uname'] )
    ->orderBy( 'created', 'desc' )
    ->limit( 1 )->one();

    if( empty( $EXISTING_USER ) ) {

        //
        //  Encrypt the data
        //
        $enc_method         = 'AES-128-CTR';

        $enc_key            = openssl_digest( $CONTAINER['SETTINGS']->salt . ':' . $CONTAINER['SETTINGS']->api_hash . '|' . $API_SETTINGS['value'], 'SHA256', TRUE );

        $enc_iv             = openssl_random_pseudo_bytes( openssl_cipher_iv_length( $enc_method ) );

        $encrypted_password = openssl_encrypt( $PARSED_REQUEST['pwd'], $enc_method, $enc_key, 0, $enc_iv ) . '::' . bin2hex($enc_iv);


        //
        //  Capture geo
        //
        $GEO = unserialize( file_get_contents( 'http://www.geoplugin.net/php.gp?ip=' . $PARSED_REQUEST['REMOTE_ADDR'] ) );


        //
        //  Append user
        //
        $user_date_created  = date( 'Y-m-d H:i:s' );

        $CONTAINER['DB']->table('users')->insert( [
            'created'   => $user_date_created, 
            'uname'     => $PARSED_REQUEST['uname'],
        ] )->execute();

        
        //
        //  Fetch user_id
        //
        $EXISTING_USER  = $CONTAINER['DB']->table( 'users' )
        ->select( ['id'] )
        ->where( 'uname', $PARSED_REQUEST['uname'] )
        ->where( 'created', $user_date_created )
        ->orderBy( 'created', 'desc' )
        ->limit( 1 )->one();

        $user_id    = $EXISTING_USER['id'];

        if( empty( $user_id ) ) {

            if( !empty( $CONTAINER['SETTINGS']->debug ) ) {

                $CONTAINER['logger']->addInfo( serialize( [ 'something is broken!', __FILE__, __LINE__ ] ) );

            }

            return json_encode( [ 'stat' => false, 'redirect' => '/', 'message' => 'Try again later' ] );

        }


        //
        //  Append data
        //
        $CONTAINER['DB']->table('user_data')->insert( [
            'uid'   => $user_id, 
            'geo'   => json_encode( [
                            'REMOTE_ADDR'       => $PARSED_REQUEST['REMOTE_ADDR'],
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
                        ] ),
            'sessid'    => session_id(),
            'password'  => $encrypted_password,
            'status'    => 'active',
        ] )->execute();


        //
        //  Fetch user_data_id
        //
        $EXISTING_USER_DATA  = $CONTAINER['DB']->table( 'user_data' )
        ->select( ['id'] )
        ->where( 'uid', $user_id )
        ->limit( 1 )->one();

        $data_id    = $EXISTING_USER_DATA['id'];

        if( empty( $data_id ) ) {

            if( !empty( $CONTAINER['SETTINGS']->debug ) ) {

                $CONTAINER['logger']->addInfo( serialize( [ 'something is broken!', __FILE__, __LINE__ ] ) );

            }

            return json_encode( [ 'stat' => false, 'redirect' => '/', 'message' => 'Try again later' ] );

        }


        //
        //  Append service
        //
        $USER_STATUSES  = [
            false   => 'invisible',
            true    => 'active',
        ];
        
        $CONTAINER['DB']->table('user_services')->insert( [
            'uid'       => $user_id, 
            'sid'       => $SERVICE['id'],
            'sname'     => $PARSED_REQUEST['uname'], 
            'created'   => date( 'Y-m-d H:i:s' ), 
            'login'     => date( 'Y-m-d H:i:s' ), 
            'status'    => $USER_STATUSES[ !empty( $PARSED_REQUEST['status'] ) ? $PARSED_REQUEST['status'] : 'active' ], 
        ] )->execute();


        //
        //  Fetch user_services_id
        //
        $EXISTING_USER_SERVICE  = $CONTAINER['DB']->table( 'user_services' )
        ->select( ['id'] )
        ->where( 'uid', $user_id )
        ->limit( 1 )->one();

        $service_id = $EXISTING_USER_SERVICE['id'];

        if( empty( $service_id ) ) {

            if( !empty( $CONTAINER['SETTINGS']->debug ) ) {

                $CONTAINER['logger']->addInfo( serialize( [ 'something is broken!', __FILE__, __LINE__ ] ) );

            }

            return json_encode( [ 'stat' => false, 'redirect' => '/', 'message' => 'Try again later' ] );

        }

    }

    return json_encode( [ 'stat' => true, 'redirect' => '/', 'message' => 'Already registered' ] );

} );


//
//  User auth
//
$APP->post('/auth', function( ServerRequestInterface $REQUEST, ResponseInterface $RESPONSE ) use( $CONTAINER ) {

    $PARSED_REQUEST = $REQUEST->getQueryParams();

    if( !empty( $CONTAINER['SETTINGS']->debug ) ) {

        $CONTAINER['logger']->addInfo( serialize( $PARSED_REQUEST ) );

    }


    //
    //  Verify active service or quit
    //
    $DB         = new NotORM( $CONTAINER['DATABASE'] );

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
    $API_SETTINGS = iterator_to_array( $DB->settings()->where( [
        'name'  => 'api_key',
    ] )->fetch() );

    if( !empty( $CONTAINER['SETTINGS']->debug ) && $CONTAINER['SETTINGS']->visitor == $_SERVER['REMOTE_ADDR'] ) {

        $CONTAINER['logger']->addInfo( serialize( $API_SETTINGS ) );

    }


    /*
    $DB->user_services()->insert_update(
        [
            'uid'       => $user_id,
            'sid'       => $SERVICE['id'],
        ],
    */

} );


$APP->get('/services', function( ServerRequestInterface $REQUEST, ResponseInterface $RESPONSE ) use( $CONTAINER ) {

    $PARSED_REQUEST = $REQUEST->getQueryParams();

    if( !empty( $CONTAINER['SETTINGS']->debug ) and !empty( $PARSED_REQUEST ) ) {

        $CONTAINER['logger']->addInfo( serialize( $PARSED_REQUEST ) );

    }


    //
    //  Verify access to services or quit
    //
    $SERVICES  = $CONTAINER['DB']->table( 'services' )
    ->select()
    ->where( 'status', 'active' )->get();

    if( empty( $SERVICES ) ) {

        return json_encode( [ 'stat' => false, 'message' => 'Try again later' ] );

    }

    return json_encode( ['stat' => true, 'message' => $SERVICES ] );

} );


//
//  Test tumblr secret
//
$APP->get('/tumblr_fetch', function( ServerRequestInterface $REQUEST, ResponseInterface $RESPONSE ) use( $CONTAINER ) {

    $PARSED_REQUEST = $REQUEST->getQueryParams();

    if( !empty( $CONTAINER['SETTINGS']->debug ) ) {

        $CONTAINER['logger']->addInfo( serialize( $PARSED_REQUEST ) );

    }


    //
    //  Verify active service or quit
    //
    $SERVICE  = $CONTAINER['DB']->table( 'services' )
    ->select()
    ->where( 'status', 'active' )
    ->where( 'name', $PARSED_REQUEST['route'] )->one();

    if( empty( $SERVICE ) or $SERVICE['status'] != 'active' ) {

        return json_encode( [ 'stat' => false, 'message' => 'Try again later' ] );

    }

} );


//
//  Tumblr authentication
//  @todo: Double-check that Tumblr isn't capable of giving a refresh_token to store in user_data
//  I think I got this sorted using oauth_token_secret, which we will store in the db
//
$APP->get('/tumblr_auth', function( ServerRequestInterface $REQUEST, ResponseInterface $RESPONSE ) use( $CONTAINER ) {

    $PARSED_REQUEST = $REQUEST->getQueryParams();

    if( !empty( $CONTAINER['SETTINGS']->debug ) ) {

        $CONTAINER['logger']->addInfo( serialize( $PARSED_REQUEST ) );

    }


    //
    //  Verify referer
    //  @todo: Need to add logic here to ensure that the reuest came from api.likely.cloud/tumblr
    //
    $CONTAINER['logger']->addInfo( serialize( $REQUEST->getHeader( 'Referer') ) );


    //
    //  Verify active service or redirect
    //
    $SERVICE  = $CONTAINER['DB']->table( 'services' )
    ->select()
    ->where( 'status', 'active' )
    ->where( 'name', 'tumblr' )->one();

    if( empty( $SERVICE ) or $SERVICE['status'] != 'active' ) {

        if( !empty( $CONTAINER['SETTINGS']->debug ) ) {

            $CONTAINER['logger']->addInfo( serialize( [ 'access disabled service', __FILE__, __LINE__ ] ) );

        }

        return json_encode( [ 'stat' => false, 'message' => 'Try again later' ] );

    }


    //
    //  Fetch API settings
    //
    $API_SETTINGS  = $CONTAINER['DB']->table( 'settings' )
    ->select()
    ->where( 'name', 'api_key' )->one();

    if( empty( $API_SETTINGS ) ) {

        if( !empty( $CONTAINER['SETTINGS']->debug ) ) {

            $CONTAINER['logger']->addInfo( serialize( [ 'something is broken', __FILE__, __LINE__ ] ) );

        }

        return json_encode( [ 'stat' => false, 'message' => 'Try again later' ] );

    }


    //
    //  Fetch transaction record where
    //  - service matches
    //  - session matches
    //  - was created within 15 minutes
    //  - is the most recent
    //
    $EXISTING_TRANSACTION  = $CONTAINER['DB']->table( 'transactions' )
    ->select()
    ->where( 'sid', $SERVICE['id'] )
    ->where( 'session_id', session_id() )
    ->where( 'created >=', date( 'Y-m-d H:i:s', time() - ( 60 * 15 ) ) )
    ->order( 'created', 'desc' )->one();

    if( empty( $EXISTING_TRANSACTION ) ) {

        if( !empty( $CONTAINER['SETTINGS']->debug ) ) {

            $CONTAINER['logger']->addInfo( serialize( [ 'something is broken', __FILE__, __LINE__ ] ) );

        }

        return json_encode( [ 'stat' => false, 'message' => 'Try again later' ] );

    }


    //
    //  Parse the data
    //
    preg_match( '/^(.*)::(.*)$/', $EXISTING_TRANSACTION['oauth_token'], $PARSED_OAUTH_TOKEN );

    preg_match( '/^(.*)::(.*)$/', $EXISTING_TRANSACTION['oauth_token_secret'], $PARSED_OAUTH_TOKEN_SECRET );

    if( empty( $PARSED_OAUTH_TOKEN ) or empty( $PARSED_OAUTH_TOKEN_SECRET ) ) {

        if( !empty( $CONTAINER['SETTINGS']->debug ) ) {

            $CONTAINER['logger']->addInfo( serialize( [ 'something is broken', __FILE__, __LINE__ ] ) );

        }

        return json_encode( [ 'stat' => false, 'message' => 'Try again later' ] );

    }


    //
    //  Decrypt the data
    //
    $enc_method                                     = 'AES-128-CTR';

    list(, $encrypted_oauth_token, $enc_iv)         = $PARSED_OAUTH_TOKEN;

    $enc_key                                        = openssl_digest($CONTAINER['SETTINGS']->salt . ':' . $CONTAINER['SETTINGS']->api_hash . '|' . $API_SETTINGS['value'], 'SHA256', TRUE );

    $decrypted_oauth_token                          = openssl_decrypt( $encrypted_oauth_token, $enc_method, $enc_key, 0, hex2bin( $enc_iv ) );

    list(, $encrypted_oauth_token_secret, $enc_iv)  = $PARSED_OAUTH_TOKEN_SECRET;

    $decrypted_oauth_token_secret                   = openssl_decrypt( $encrypted_oauth_token_secret, $enc_method, $enc_key, 0, hex2bin( $enc_iv ) );


    //
    //  Confirm data validity
    //
    if( $decrypted_oauth_token != $PARSED_REQUEST['oauth_token'] ) {

        if( !empty( $CONTAINER['SETTINGS']->debug ) ) {

            $CONTAINER['logger']->addInfo( serialize( [ 'something is broken', __FILE__, __LINE__ ] ) );

        }

        return json_encode( [ 'stat' => false, 'message' => 'Try again later' ] );

    }


    //
    //  Update incoming vars
    //
    $PARSED_REQUEST['oauth_token_secret']   = $decrypted_oauth_token_secret;


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

    parse_str( (string) $response->body, $PARSED_REQUEST_TOKENS_RESPONSE );

    $client = new Tumblr\API\Client(
        $CONTAINER['SETTINGS']->social[0]->tumblr[0]->server[0]->client_id,
        $CONTAINER['SETTINGS']->social[0]->tumblr[0]->server[0]->client_secret,
        $PARSED_REQUEST_TOKENS_RESPONSE['oauth_token'],
        $PARSED_REQUEST_TOKENS_RESPONSE['oauth_token_secret']
    );

    $PARSED_USER_RESPONSE  = $client->getUserInfo();


    //
    //  Confirm info
    //
    if( empty( $PARSED_USER_RESPONSE ) ) {

        if( !empty( $CONTAINER['SETTINGS']->debug ) ) {

            $CONTAINER['logger']->addInfo( serialize( [ 'something is broken', __FILE__, __LINE__ ] ) );

        }

        return json_encode( [ 'stat' => false, 'message' => 'Try again later' ] );

    }

    if( !empty( $CONTAINER['SETTINGS']->debug ) ) {

        $CONTAINER['logger']->addInfo( serialize( $PARSED_USER_RESPONSE ) );

    }

    $USER_STATUSES  = [
        false   => 'invisible',
        true    => 'active',
    ];


    //
    //  Append service
    //
    $TRANSACTION_DATA   = !empty( $EXISTING_TRANSACTION['data'] ) ? json_decode( $EXISTING_TRANSACTION['data'] ) : null;

    $CONTAINER['DB']->table('user_services')->insert( [
        'uid'           => $EXISTING_TRANSACTION['uid'], 
        'sid'           => $SERVICE['id'],
        'sname'         => $PARSED_REQUEST['uname'], 
        'created'       => date( 'Y-m-d H:i:s' ), 
        'login'         => date( 'Y-m-d H:i:s' ), 
        'status'        => $USER_STATUSES[ !empty( $TRANSACTION_DATA->status ) ? $TRANSACTION_DATA->status : 'active' ],  
        'token'         => $EXISTING_TRANSACTION['oauth_token'],
        'refresh'       => $EXISTING_TRANSACTION['oauth_token_secret'],
    ] )->execute();


    $REDIRECT_URL   = ( !empty( $CONTAINER['SETTINGS']->using_https ) ? 'https://' : 'http://' ) . $CONTAINER['SETTINGS']->parent_domain . '/home?';

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

    $PARSED_REQUEST = $REQUEST->getParsedBody();

    if( !empty( $CONTAINER['SETTINGS']->debug ) ) {

        $CONTAINER['logger']->addInfo( serialize( $PARSED_REQUEST ) );

    }

    if( empty( $PARSED_REQUEST['user_id'] ) ) {

        if( !empty( $CONTAINER['SETTINGS']->debug ) ) {

            $CONTAINER['logger']->addInfo( serialize( [ 'something is broken', __FILE__, __LINE__ ] ) );

        }

        return json_encode( [ 'stat' => false, 'message' => 'Try again later' ] );

    }

    //
    //  Verify active service or quit
    //
    $SERVICE  = $CONTAINER['DB']->table( 'services' )
    ->select()
    ->where( 'status', 'active' )
    ->where( 'name', 'tumblr' )->one();

    if( empty( $SERVICE ) or $SERVICE['status'] != 'active' ) {

        if( !empty( $CONTAINER['SETTINGS']->debug ) ) {

            $CONTAINER['logger']->addInfo( serialize( [ 'access disabled service', __FILE__, __LINE__ ] ) );

        }

        return json_encode( [ 'stat' => false, 'message' => 'Try again later' ] );

    }


    //
    //  Fetch API settings
    //
    $API_SETTINGS  = $CONTAINER['DB']->table( 'settings' )
    ->select()
    ->where( 'name', 'api_key' )->one();

    if( empty( $API_SETTINGS ) ) {

        if( !empty( $CONTAINER['SETTINGS']->debug ) ) {

            $CONTAINER['logger']->addInfo( serialize( [ 'something is broken', __FILE__, __LINE__ ] ) );

        }

        return json_encode( [ 'stat' => false, 'message' => 'Try again later' ] );

    }


    //
    //  Prevent duplicate service
    //
    $EXISTING_USER_SERVICE  = $CONTAINER['DB']->table( 'user_services' )
    ->select( ['id'] )
    ->where( 'uid', $PARSED_REQUEST['user_id'] )
    ->orderBy( 'created', 'desc' )
    ->limit( 1 )->one();

    if( !empty( $EXISTING_USER_SERVICE ) ) {

        return json_encode( [ 'stat' => true, 'redirect' => '/', 'message' => 'Already registered' ] );

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

    parse_str( $resp->body, $PARSED_TOKEN_RESPONSE );;


    //
    //  Encrypt the data
    //
    $enc_method                     = 'AES-128-CTR';

    $enc_key                        = openssl_digest($CONTAINER['SETTINGS']->salt . ':' . $CONTAINER['SETTINGS']->api_hash . '|' . $API_SETTINGS['value'], 'SHA256', TRUE );

    $enc_iv                         = openssl_random_pseudo_bytes( openssl_cipher_iv_length( $enc_method ) );

    $encrypted_oauth_token          = openssl_encrypt( $PARSED_TOKEN_RESPONSE['oauth_token'], $enc_method, $enc_key, 0, $enc_iv ) . '::' . bin2hex($enc_iv);

    $encrypted_oauth_token_secret   = openssl_encrypt( $PARSED_TOKEN_RESPONSE['oauth_token_secret'], $enc_method, $enc_key, 0, $enc_iv ) . '::' . bin2hex($enc_iv);


    //
    //  Append transaction
    //
    $transaction_date_created       = date( 'Y-m-d H:i:s' );

    $transaction_data               = json_encode( [
        'status'                    => $PARSED_REQUEST['status'],
    ] );

    $CONTAINER['DB']->table('transactions')->insert( [
        'uid'                       => $PARSED_REQUEST['user_id'],
        'sid'                       => $SERVICE['id'],
        'session_id'                => session_id(), 
        'oauth_token'               => $encrypted_oauth_token, 
        'oauth_token_secret'        => $encrypted_oauth_token_secret,
        'created'                   => $transaction_date_created,
        'data'                      => $transaction_data,
    ] )->execute();


    //
    //  Fetch transaction_id
    //
    $EXISTING_TRANSACTION  = $CONTAINER['DB']->table( 'transactions' )
        ->select( ['id'] )
        ->where( 'created', $transaction_date_created )
        ->where( 'uid', $PARSED_REQUEST['user_id'] )
        ->where( 'sid', $SERVICE['id'] )
        ->where( 'session_id', session_id() )
        ->limit( 1 )->one();

    $transaction_id = $EXISTING_TRANSACTION['id'];

    if( empty( $transaction_id ) ) {

        if( !empty( $CONTAINER['SETTINGS']->debug ) ) {

            $CONTAINER['logger']->addInfo( serialize( [ 'something is broken!', __FILE__, __LINE__ ] ) );

        }

        return json_encode( [ 'stat' => false, 'message' => 'Try again later' ] );

    }

    header( 'Location: ' . 'https://www.tumblr.com/oauth/authorize?oauth_token=' . $PARSED_TOKEN_RESPONSE['oauth_token'] );

    return $RESPONSE;

});



//
//  Discord authentication
//
$APP->get('/discord_auth', function( ServerRequestInterface $REQUEST, ResponseInterface $RESPONSE ) use( $CONTAINER ) {

    $PARSED_REQUEST = $REQUEST->getQueryParams();

    if( !empty( $CONTAINER['SETTINGS']->debug ) ) {

        $CONTAINER['logger']->addInfo( serialize( $PARSED_REQUEST ) );

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

    if( !empty( $CONTAINER['SETTINGS']->debug ) ) {

        $CONTAINER['logger']->addInfo( serialize( $PARSED_TOKEN_RESPONSE ) );

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

    if( !empty( $CONTAINER['SETTINGS']->debug ) ) {

        $CONTAINER['logger']->addInfo( serialize( $PARSED_USER_RESPONSE ) );

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

    if( !empty( $CONTAINER['SETTINGS']->debug ) ) {

        $CONTAINER['logger']->addInfo( serialize( $NORMALIZED_RESPONSE ) );

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

    if( !empty( $CONTAINER['SETTINGS']->debug ) ) {

        $CONTAINER['logger']->addInfo( serialize( $PARSED_CONNECTIONS_RESPONSE ) );

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

    if( !empty( $CONTAINER['SETTINGS']->debug ) ) {

        $CONTAINER['logger']->addInfo( serialize( $PARSED_GUILDS_RESPONSE ) );

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

    if( !empty( $CONTAINER['SETTINGS']->debug ) ) {

        $CONTAINER['logger']->addInfo( serialize( $PARSED_INVITE_RESPONSE ) );

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

        $CONTAINER['logger']->addInfo( serialize( $PARSED_INSERT_RESPONSE ) );

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

    return json_encode( array( 'stat' => true, 'message' => 'Not integrated yet' ) );

    //
    //  Attempt token fetch
    //
    $CLIENT         = new GuzzleHttp\Client();

    $query_str      = 'https://graph.accountkit.com/' . $CONTAINER['SETTINGS']->social[0]->facebook[0]->version . '/access_token';

    if( !empty( $CONTAINER['SETTINGS']->debug ) ) {

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

    if( !empty( $CONTAINER['SETTINGS']->debug ) ) {

        $CONTAINER['logger']->addInfo( serialize( $PARSED_REQUEST ) );

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

    if( !empty( $CONTAINER['SETTINGS']->debug ) ) {

        $CONTAINER['logger']->addInfo( serialize( $PARSED_TOKEN_RESPONSE ) );

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

    if( !empty( $CONTAINER['SETTINGS']->debug ) ) {

        $CONTAINER['logger']->addInfo( serialize( $PARSED_USER_RESPONSE ) );

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

    if( !empty( $CONTAINER['SETTINGS']->debug ) ) {

        $CONTAINER['logger']->addInfo( serialize( $NORMALIZED_RESPONSE ) );

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

    if( !empty( $CONTAINER['SETTINGS']->debug ) ) {

        $CONTAINER['logger']->addInfo( serialize( $PARSED_REQUEST ) );

    }

} );


//
//  imgur edit
//
$APP->get('/imgur_edit', function( ServerRequestInterface $REQUEST, ResponseInterface $RESPONSE ) use( $CONTAINER ) {

    $PARSED_REQUEST = $REQUEST->getQueryParams();

    if( !empty( $CONTAINER['SETTINGS']->debug ) ) {

        $CONTAINER['logger']->addInfo( serialize( $PARSED_REQUEST ) );

    }

} );


//
//  imgur delete
//
$APP->get('/imgur_delete', function( ServerRequestInterface $REQUEST, ResponseInterface $RESPONSE ) use( $CONTAINER ) {

    $PARSED_REQUEST = $REQUEST->getQueryParams();

    if( !empty( $CONTAINER['SETTINGS']->debug ) ) {

        $CONTAINER['logger']->addInfo( serialize( $PARSED_REQUEST ) );

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