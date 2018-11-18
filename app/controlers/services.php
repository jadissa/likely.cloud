<?php

namespace App\controlers;

use Respect\Validation\Validator as v;

use Tumblr\API\Client as t;

use GuzzleHttp\Client as g;

class services extends controler {

	public function getActive( $REQUEST, $RESPONSE ) {

		$SERVICES  = $this->db->table( 'services' )
    	->select()
    	->where( 'status', 'active' )->get();

    	return $SERVICES;

	}


	/**
	 * 	Gets user's permission to add their tumblr account to likely
	 *
	 * 	@param 	object 	$REQUEST
	 * 	@param 	object 	$RESPONSE 
	 * 
	 * 	@return object
	 */
	public function getTumblr( $REQUEST, $RESPONSE ) {

		//
		//	Validate request
		//
		$VALIDATION		= $this->validator->validate( $REQUEST, [
			'service'	=> v::noWhitespace()->notEmpty(),
		] );

		if( $VALIDATION->failed() ) {

			return $RESPONSE->withRedirect( $this->router->pathFor( 'auth.signup' ) );

		}


		//
		//	Parse request
		//
		$PARSED_REQUEST = $REQUEST->getParsedBody();

	    if( !empty( $this->settings['debug'] ) ) {

	        $this->logger->addInfo( serialize( $PARSED_REQUEST ) );

	    }


		//
	    //  Verify active service or quit
	    //
	    $SERVICE  = $this->db->table( 'services' )
	    ->select()
	    ->where( 'status', 'active' )
	    ->where( 'name', 'tumblr' )->one();

	    if( empty( $SERVICE ) or $SERVICE['status'] != 'active' ) {

			if( !empty( $this->settings['debug'] ) ) {

				$this->logger->addInfo( serialize( [ 'access disabled service', __FILE__, __LINE__ ] ) );

			}

			$_SESSION['ERRORS']['failed']	= 'Try again later';

			return $RESPONSE->withRedirect( $this->router->pathFor( 'auth.signup' ) );

	    }


	    //
	    //  Fetch API settings or quit
	    //
		$API_SETTINGS  = $this->db->table( 'settings' )
	    ->select()
	    ->where( 'name', 'api_key' )->one();

    	if( empty( $API_SETTINGS ) ) {

			if( !empty( $this->settings['debug'] ) ) {

				$this->logger->addInfo( serialize( [ 'can not get api_key', __FILE__, __LINE__ ] ) );

			}

	        $_SESSION['ERRORS']['failed']	= 'Try again later';

			return $RESPONSE->withRedirect( $this->router->pathFor( 'auth.signup' ) );

	    }


	    //
	    //  Prevent duplicate service
	    //
	    if( !empty( $_SESSION['user'] ) and !empty( $_SESSION['user']['uid'] ) ) {

	    	$EXISTING_USER_SERVICE  = $this->db->table( 'user_services' )
		    ->select( ['id'] )
		    ->where( 'uid', $_SESSION['user']['uid'] )
		    ->where( 'sid', $SERVICE['id'] )
		    ->orderBy( 'created', 'desc' )
		    ->limit( 1 )->one();

		    if( !empty( $EXISTING_USER_SERVICE ) ) {

		    	if( !empty( $this->settings['debug'] ) ) {

					$this->logger->addInfo( serialize( [ 'user already registered', __FILE__, __LINE__ ] ) );

				}

		        return $RESPONSE->withRedirect( $this->router->pathFor( 'auth.signup' ) );

		    }

	    }


	    //
	    //  Get the user's permission
	    //
	    $CLIENT = new t(
	        $this->settings['social'][0]['tumblr'][0]['server'][0]['client_id'],
	        $this->settings['social'][0]['tumblr'][0]['server'][0]['client_secret']
	    );

	    $requestHandler = $CLIENT->getRequestHandler();

	    $requestHandler->setBaseUrl( 'https://www.tumblr.com/' );

	    $resp = $requestHandler->request( 'POST', 'oauth/request_token', [
	        'oauth_callback' => ( !empty( $this->settings['using_https'] ) ? 'https://' : 'http://' )
            . $this->settings['domain'] . $this->router->pathFor( 'service.tumblr_auth' ),
	    ] ) ;

	    parse_str( $resp->body, $PARSED_TOKEN_RESPONSE );

	    if( !empty( $this->settings['debug'] ) ) {

			$this->logger->addInfo( serialize( $PARSED_TOKEN_RESPONSE ) );

		}


	    //
	    //  Encrypt the data
	    //
	    $enc_method                     = 'AES-128-CTR';

	    $enc_key                        = openssl_digest( $this->settings['salt'] . ':' . $this->settings['api_hash'] . '|' . $API_SETTINGS['value'], 'SHA256', TRUE );

	    $enc_iv                         = openssl_random_pseudo_bytes( openssl_cipher_iv_length( $enc_method ) );

	    $encrypted_oauth_token          = openssl_encrypt( $PARSED_TOKEN_RESPONSE['oauth_token'], $enc_method, $enc_key, 0, $enc_iv ) . '::' . bin2hex( $enc_iv );

	    $encrypted_oauth_token_secret   = openssl_encrypt( $PARSED_TOKEN_RESPONSE['oauth_token_secret'], $enc_method, $enc_key, 0, $enc_iv ) . '::' . bin2hex( $enc_iv );


	    //
	    //  Append transaction
	    //
	    $USER_STATUSES  = [
            false   => 'invisible',
            true    => 'public',
        ];

	    $transaction_date_created       = date( 'Y-m-d H:i:s' );

	    $transaction_data               = json_encode( [
	        'status'                    => $USER_STATUSES[ !empty( $PARSED_REQUEST['status'] ) ? true : false ],
	    ] );

	    $this->db->table('transactions')->insert( [
	        'sid'                       => $SERVICE['id'],
	        'session_id'                => session_id(), 
	        'oauth_token'               => $encrypted_oauth_token, 
	        'oauth_token_secret'        => $encrypted_oauth_token_secret,
	        'created'                   => $transaction_date_created,
	        'data'                      => $transaction_data,
	    ] )->execute();

	    header( 'Location: ' . 'https://www.tumblr.com/oauth/authorize?oauth_token=' . $PARSED_TOKEN_RESPONSE['oauth_token'] );

	    return $RESPONSE;

	}


	/**
	 * 	Adds user's tumblr account to likely
	 *
	 * 	@param 	object 	$REQUEST
	 * 	@param 	object 	$RESPONSE 
	 * 
	 * 	@return object
	 */
	public function authTumblr( $REQUEST, $RESPONSE ) {

		//
		//	Validate request
		//
		$VALIDATION		= $this->validator->validate( $REQUEST, [
			'oauth_token'		=> v::noWhitespace()->notEmpty(),
			'oauth_verifier'	=> v::noWhitespace()->notEmpty(),
		] );

		if( $VALIDATION->failed() ) {

			return $RESPONSE->withRedirect( $this->router->pathFor( 'auth.signup' ) );

		}


		//
	    //  Verify active service or quit
	    //
	    $SERVICE  = $this->db->table( 'services' )
	    ->select()
	    ->where( 'status', 'active' )
	    ->where( 'name', 'tumblr' )->one();

	    if( empty( $SERVICE ) or $SERVICE['status'] != 'active' ) {

			if( !empty( $this->settings['debug'] ) ) {

				$this->logger->addInfo( serialize( [ 'access disabled service', __FILE__, __LINE__ ] ) );

			}

			$_SESSION['ERRORS']['failed']	= 'Try again later';

			return $RESPONSE->withRedirect( $this->router->pathFor( 'auth.signup' ) );

	    }


	    //
	    //  Fetch API settings or quit
	    //
		$API_SETTINGS  = $this->db->table( 'settings' )
	    ->select()
	    ->where( 'name', 'api_key' )->one();

    	if( empty( $API_SETTINGS ) ) {

			if( !empty( $this->settings['debug'] ) ) {

				$this->logger->addInfo( serialize( [ 'can not get api_key', __FILE__, __LINE__ ] ) );

			}

	        $_SESSION['ERRORS']['failed']	= 'Try again later';

			return $RESPONSE->withRedirect( $this->router->pathFor( 'auth.signup' ) );

	    }


	    //
	    //  Fetch transaction record where
	    //  - service matches
	    //  - session matches
	    //  - was created within 15 minutes
	    //  - is the most recent
	    //
	    $EXISTING_TRANSACTION  = $this->db->table( 'transactions' )
	    ->select()
	    ->where( 'sid', $SERVICE['id'] )
	    ->where( 'session_id', session_id() )
	    ->where( 'created', '>=', date( 'Y-m-d H:i:s', time() - ( 60 * 15 ) ) )
	    ->orderBy( 'created', 'desc' )->one();

	    if( empty( $EXISTING_TRANSACTION ) ) {

	        if( !empty( $this->settings['debug'] ) ) {

	        	$this->logger->addInfo( serialize( [ 'something is broken ' . session_id(), __FILE__, __LINE__ ] ) );

	        }

	        return json_encode( [ 'stat' => false, 'message' => 'Try again later' ] );

	    }


	    //
	    //  Parse the data
	    //
	    preg_match( '/^(.*)::(.*)$/', $EXISTING_TRANSACTION['oauth_token'], $PARSED_OAUTH_TOKEN );

	    preg_match( '/^(.*)::(.*)$/', $EXISTING_TRANSACTION['oauth_token_secret'], $PARSED_OAUTH_TOKEN_SECRET );

	    if( empty( $PARSED_OAUTH_TOKEN ) or empty( $PARSED_OAUTH_TOKEN_SECRET ) ) {

	        if( !empty( $this->settings['debug'] ) ) {

	            $this->logger->addInfo( serialize( [ 'something is broken', __FILE__, __LINE__ ] ) );

	        }

	        return $RESPONSE->withRedirect( $this->router->pathFor( 'auth.signup' ) );

	    }


	    //
	    //  Decrypt the data
	    //
	    $enc_method                                     = 'AES-128-CTR';

	    list(, $encrypted_oauth_token, $enc_iv)         = $PARSED_OAUTH_TOKEN;

	    $enc_key                        				= openssl_digest( $this->settings['salt'] . ':' . $this->settings['api_hash'] . '|' . $API_SETTINGS['value'], 'SHA256', TRUE );

	    $decrypted_oauth_token                          = openssl_decrypt( $encrypted_oauth_token, $enc_method, $enc_key, 0, hex2bin( $enc_iv ) );

	    list(, $encrypted_oauth_token_secret, $enc_iv)  = $PARSED_OAUTH_TOKEN_SECRET;

	    $decrypted_oauth_token_secret                   = openssl_decrypt( $encrypted_oauth_token_secret, $enc_method, $enc_key, 0, hex2bin( $enc_iv ) );


	    //
	    //  Confirm data validity
	    //
	    if( $decrypted_oauth_token != $REQUEST->getParam( 'oauth_token' ) ) {

	        if( !empty( $this->settings['debug'] ) ) {

	            $this->logger->addInfo( serialize( [ 'something is broken', __FILE__, __LINE__ ] ) );

	        }

	        return $RESPONSE->withRedirect( $this->router->pathFor( 'auth.signup' ) );

	    }


	    //
	    //  Proceed with service auth
	    //
	    $CLIENT = new t(
	        $this->settings['social'][0]['tumblr'][0]['server'][0]['client_id'],
	        $this->settings['social'][0]['tumblr'][0]['server'][0]['client_secret'],
	        $REQUEST->getParam( 'oauth_token' ), $decrypted_oauth_token_secret
	    );


	    $requestHandler = $CLIENT->getRequestHandler();

	    $requestHandler->setBaseUrl( 'https://www.tumblr.com/' );

	    $response = $requestHandler->request('POST', 'oauth/access_token', [
	        'oauth_verifier' => $REQUEST->getParam( 'oauth_verifier' ),
	    ]);

	    parse_str( (string) $response->body, $PARSED_REQUEST_TOKENS_RESPONSE );

	    $CLIENT = new t(
	        $this->settings['social'][0]['tumblr'][0]['server'][0]['client_id'],
	        $this->settings['social'][0]['tumblr'][0]['server'][0]['client_secret'],
	        $PARSED_REQUEST_TOKENS_RESPONSE['oauth_token'],
	        $PARSED_REQUEST_TOKENS_RESPONSE['oauth_token_secret']
	    );

	    $PARSED_USER_RESPONSE  = $CLIENT->getUserInfo();

	    #print'<pre>';print_r( $PARSED_USER_RESPONSE );print'</pre>';exit;


	    //
	    //  Confirm info
	    //
	    if( empty( $PARSED_USER_RESPONSE ) ) {

	        if( !empty( $this->settings['debug'] ) ) {

	            $this->logger->addInfo( serialize( [ 'something is broken', __FILE__, __LINE__ ] ) );

	        }

	        return json_encode( [ 'stat' => false, 'message' => 'Try again later' ] );

	    }

	    if( !empty( $this->settings['debug'] ) ) {

	        $this->logger->addInfo( serialize( $PARSED_USER_RESPONSE ) );

	    }


	    //
	    //  Fetch user
	    //
	    $EXISTING_USER  = $this->db->table( 'users' )
	    ->select( ['id'] )
	    ->where( 'uname', $PARSED_USER_RESPONSE->user->name )
	    ->orderBy( 'created', 'desc' )
	    ->limit( 1 )->one();

	    if( !empty( $EXISTING_USER ) ) {

	    	$_SESSION['ERRORS']['failed']	= 'Username already taken';

			return $RESPONSE->withRedirect( $this->router->pathFor( 'home' ) );

	    }


	    //
        //  Capture geo
        //
        $GEO = unserialize( file_get_contents( 'http://www.geoplugin.net/php.gp?ip=' . $_SERVER['REMOTE_ADDR'] ) );


        //
        //  Append user
        //
        $user_date_created  = date( 'Y-m-d H:i:s' );

        $this->db->table('users')->insert( [
            'created'   => $user_date_created, 
            'uname'     => $PARSED_USER_RESPONSE->user->name,
        ] )->execute();


        //
        //  Fetch user_id
        //
        $EXISTING_USER  = $this->db->table( 'users' )
        ->select( ['id'] )
        ->where( 'uname', $PARSED_USER_RESPONSE->user->name )
        ->where( 'created', $user_date_created )
        ->orderBy( 'created', 'desc' )
        ->limit( 1 )->one();

        $user_id    = $EXISTING_USER['id'];

        if( empty( $user_id ) ) {

			if( !empty( $this->settings['debug'] ) ) {

				$this->logger->addInfo( serialize( [ 'access disabled service', __FILE__, __LINE__ ] ) );

			}

            $_SESSION['ERRORS']['failed']	= 'Try again later';

			return $RESPONSE->withRedirect( $this->router->pathFor( 'auth.signup' ) );

        }


        //
		//  Encrypt password
		//
		$password 			= md5( uniqid( mt_rand() ) );

		$enc_method         = 'AES-128-CTR';

		$enc_key            = openssl_digest( $this->settings['salt'] . ':' . $this->settings['api_hash'] . '|' . $API_SETTINGS['value'], 'SHA256', TRUE );

		$enc_iv             = openssl_random_pseudo_bytes( openssl_cipher_iv_length( $enc_method ) );

		$encrypted_password = openssl_encrypt( $password, $enc_method, $enc_key, 0, $enc_iv ) . '::' . bin2hex( $enc_iv );


        //
        //  Append data
        //
        $this->db->table('user_data')->insert( [
            'uid'   => $user_id, 
            'email'	=> null,
            'geo'   => json_encode( [
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
                        ] ),
            'sessid'    => session_id(),
            'password'  => $encrypted_password,
            'status'    => 'registered',
        ] )->execute();


        //
        //  Fetch user_data_id
        //
        $EXISTING_USER_DATA  = $this->db->table( 'user_data' )
        ->select( ['id'] )
        ->where( 'uid', $user_id )
        ->limit( 1 )->one();

        $data_id    = $EXISTING_USER_DATA['id'];

        if( empty( $data_id ) ) {

			if( !empty( $this->settings['debug'] ) ) {

				$this->logger->addInfo( serialize( [ 'access disabled service', __FILE__, __LINE__ ] ) );

			}

            $_SESSION['ERRORS']['failed']	= 'Try again later';

			return $RESPONSE->withRedirect( $this->router->pathFor( 'auth.signup' ) );

        }


	    //
	    //  Append service
	    //
	    $USER_STATUSES  = [
            false   => 'invisible',
            true    => 'public',
        ];

	    $TRANSACTION_DATA   = !empty( $EXISTING_TRANSACTION['data'] ) ? json_decode( $EXISTING_TRANSACTION['data'] ) : null;

	    $this->db->table('user_services')->insert( [
	        'uid'           => $user_id, 
	        'sid'           => $SERVICE['id'],
	        'sname'         => $PARSED_USER_RESPONSE->user->name, 
	        'created'       => date( 'Y-m-d H:i:s' ), 
	        'login'         => date( 'Y-m-d H:i:s' ), 
	        'status'        => $USER_STATUSES[ !empty( $EXISTING_TRANSACTION['status'] ) ] ? true : false, 
	        'token'         => $EXISTING_TRANSACTION['oauth_token'],
	        'refresh'       => $EXISTING_TRANSACTION['oauth_token_secret'],
	    ] )->execute();


	    //
        //	Update session
        //
        if( !empty( $_SESSION['user']['SERVICES'] ) ) {

        	array_push( $_SESSION['user']['SERVICES'], [
				'tumblr'					=> [ 'status', $USER_STATUSES[ !empty( $REQUEST->getParam( 'status' ) ) ? true : false ] ],
			] );

        } else {

        	$_SESSION['user']['SERVICES']	= [
				'tumblr'					=> [ 'status', $USER_STATUSES[ !empty( $REQUEST->getParam( 'status' ) ) ? true : false ] ],
			];

        }
        
		$_SESSION['user']['last_updated']	= date( 'Y-m-d H:i:s' );

	    return $RESPONSE->withRedirect( $this->router->pathFor( 'home' ) );

	}

}