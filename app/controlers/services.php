<?php

namespace App\controlers;

use App\controlers\utility\crypt as c;

use Respect\Validation\Validator as v;

use Tumblr\API\Client as t;

use GuzzleHttp\Client as g;

class services extends controler {

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
			'uname'		=> v::noWhitespace()->notEmpty()->alpha(),
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

			$this->flash->addMessage( 'error', 'Try again later' );

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

	        $this->flash->addMessage( 'error', 'Try again later' );

			return $RESPONSE->withRedirect( $this->router->pathFor( 'auth.signup' ) );

	    }


	    //
	    //  Prevent duplicate service
	    //
	    $AUTH 	= new auth;
	    
	    if( !empty( $AUTH->authenticated() ) ) {

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

				$this->flash->addMessage( 'error', 'Try again later' );

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
	    $CRYPT 			= new c( $this->CONTAINER );

	    $ENCRYPTED_DATA	= $CRYPT->encrypt( $PARSED_TOKEN_RESPONSE, $this->settings['api_hash'] );


	    //
	    //  Append transaction
	    //
	    $USER_STATUSES  = [
            false   => 'invisible',
            true    => 'public',
        ];

	    $transaction_date_created       = date( 'Y-m-d H:i:s' );

	    $transaction_data               = json_encode( [
	        'status'                    => $USER_STATUSES[ !empty( $REQUEST->getParam( 'status' ) ) ? true : false ],
	        'uname'						=> $REQUEST->getParam( 'uname' ),
	    ] );

	    $this->db->table('transactions')->insert( [
	        'sid'                       => $SERVICE['id'],
	        'session_id'                => session_id(), 
	        'oauth_token'               => $ENCRYPTED_DATA['oauth_token'], 
	        'oauth_token_secret'        => $ENCRYPTED_DATA['oauth_token_secret'],
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

			$this->flash->addMessage( 'error', 'Try again later' );

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

			$this->flash->addMessage( 'error', 'Try again later' );

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

	        $this->flash->addMessage( 'error', 'Try again later' );

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
	    ->select( [ 'oauth_token', 'oauth_token_secret', 'data' ] )
	    ->where( 'sid', $SERVICE['id'] )
	    ->where( 'session_id', session_id() )
	    ->where( 'created', '>=', date( 'Y-m-d H:i:s', time() - ( 60 * 15 ) ) )
	    ->orderBy( 'created', 'desc' )->one();

	    if( empty( $EXISTING_TRANSACTION ) ) {

	        if( !empty( $this->settings['debug'] ) ) {

	        	$this->logger->addInfo( serialize( [ 'something is broken ' . session_id(), __FILE__, __LINE__ ] ) );

	        }

	        $this->flash->addMessage( 'error', 'Try again later' );

	        return $RESPONSE->withRedirect( $this->router->pathFor( 'auth.signup' ) );

	    }


	    //
	    //	Parse data field
	    //
	    if( empty( $EXISTING_TRANSACTION['data' ] ) ) {

	    	if( !empty( $this->settings['debug'] ) ) {

	        	$this->logger->addInfo( serialize( [ 'something is broken ' . session_id(), __FILE__, __LINE__ ] ) );

	        }

	        $this->flash->addMessage( 'error', 'Try again later' );

	        return $RESPONSE->withRedirect( $this->router->pathFor( 'auth.signup' ) );

	    }

	    if( !empty( $this->settings['debug'] ) ) {

	        	$this->logger->addInfo( serialize( [ $EXISTING_TRANSACTION, __FILE__, __LINE__ ] ) );

	        }

	    $DATA_FIELD	= json_decode( $EXISTING_TRANSACTION['data' ] );

	    unset( $EXISTING_TRANSACTION['data' ] );

	    if( !empty( $this->settings['debug'] ) ) {

	        	$this->logger->addInfo( serialize( [ $EXISTING_TRANSACTION, __FILE__, __LINE__ ] ) );

	        }
	        exit( 'exiting ' . __FILE__ . ' ' . __LINE__ );


	    //
	    //  Decrypt the data
	    //
	    $CRYPT 			= new c( $this->CONTAINER );

	    $DECRYPTED_DATA = $CRYPT->decrypt( $EXISTING_TRANSACTION, $this->settings['api_hash'] );

	    if( empty( $DECRYPTED_DATA ) ) {

	        if( !empty( $this->settings['debug'] ) ) {

	            $this->logger->addInfo( serialize( [ 'something is broken', __FILE__, __LINE__ ] ) );

	        }

	        $this->flash->addMessage( 'error', 'Try again later' );

	        return $RESPONSE->withRedirect( $this->router->pathFor( 'auth.signup' ) );

	    }


	    //
	    //  Confirm data validity
	    //
	    if( $DECRYPTED_DATA['oauth_token'] != $REQUEST->getParam( 'oauth_token' ) ) {

	        if( !empty( $this->settings['debug'] ) ) {

	            $this->logger->addInfo( serialize( [ 'something is broken', __FILE__, __LINE__ ] ) );

	        }

	        $this->flash->addMessage( 'error', 'Try again later' );

	        return $RESPONSE->withRedirect( $this->router->pathFor( 'auth.signup' ) );

	    }


	    //
	    //  Proceed with service auth
	    //
	    $CLIENT = new t(
	        $this->settings['social'][0]['tumblr'][0]['server'][0]['client_id'],
	        $this->settings['social'][0]['tumblr'][0]['server'][0]['client_secret'],
	        $REQUEST->getParam( 'oauth_token' ), $DECRYPTED_DATA['oauth_token_secret']
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

	    if( !empty( $this->settings['debug'] ) ) {

	        $this->logger->addInfo( serialize( [ $PARSED_REQUEST_TOKENS_RESPONSE, __LINE__ ] ) );

	    }

	    $PARSED_USER_RESPONSE  = $CLIENT->getUserInfo();


	    //
	    //  Confirm info
	    //
	    if( empty( $PARSED_USER_RESPONSE ) ) {

	        if( !empty( $this->settings['debug'] ) ) {

	            $this->logger->addInfo( serialize( [ 'something is broken', __FILE__, __LINE__ ] ) );

	        }

	        $this->flash->addMessage( 'error', 'Try again later' );

	        return $RESPONSE->withRedirect( $this->router->pathFor( 'auth.signup' ) );

	    }

	    if( !empty( $this->settings['debug'] ) ) {

	        $this->logger->addInfo( serialize( $PARSED_USER_RESPONSE ) );

	    }


	    //
	    //  Fetch user
	    //
	    $EXISTING_USER  = $this->db->table( 'users' )
	    ->select( ['id'] )
	    ->where( 'uname', $DATA_FIELD->uname )
	    ->orderBy( 'created', 'desc' )
	    ->limit( 1 )->one();

	    if( !empty( $EXISTING_USER ) ) {

	    	$this->flash->addMessage( 'error', 'Username already taken' );

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
            'uname'     => $DATA_FIELD->uname,
        ] )->execute();


        //
        //  Fetch user_id
        //
        $EXISTING_USER  = $this->db->table( 'users' )
        ->select( ['id'] )
        ->where( 'uname', $DATA_FIELD->uname )
        ->where( 'created', $user_date_created )
        ->orderBy( 'created', 'desc' )
        ->limit( 1 )->one();

        $user_id    = $EXISTING_USER['id'];

        if( empty( $user_id ) ) {

			if( !empty( $this->settings['debug'] ) ) {

				$this->flash->addMessage( 'error', 'Username already taken' );

				$this->logger->addInfo( serialize( [ 'access disabled service', __FILE__, __LINE__ ] ) );

			}

            $this->flash->addMessage( 'error', 'Try again later' );

			return $RESPONSE->withRedirect( $this->router->pathFor( 'auth.signup' ) );

        }


        //
	    //  Encrypt password
	    //
	    $password 		= md5( uniqid( mt_rand() ) );

	    $CRYPT 			= new c( $this->CONTAINER );

	    $ENCRYPTED_DATA	= $CRYPT->encrypt( ['password' => $password ], $this->settings['api_hash'] );


        //
        //  Append data
        //
        $this->db->table('user_data')->insert( [
            'uid'   	=> $user_id, 
            'modified'	=> $user_date_created,
            'email'		=> null,
            'geo'   	=> json_encode( [
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
            'password'  => $ENCRYPTED_DATA['password'],
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

            $this->flash->addMessage( 'error', 'Try again later' );

			return $RESPONSE->withRedirect( $this->router->pathFor( 'auth.signup' ) );

        }


        //
        //	Encrypt long-lasting deets
        //
        $CRYPT 			= new c( $this->CONTAINER );

	    $ENCRYPTED_DATA	= $CRYPT->encrypt( $PARSED_REQUEST_TOKENS_RESPONSE, $this->settings['api_hash'] );


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
	        'sname'         => $DATA_FIELD->uname, 
	        'created'       => date( 'Y-m-d H:i:s' ), 
	        'login'         => date( 'Y-m-d H:i:s' ), 
	        'status'        => $USER_STATUSES[ !empty( $EXISTING_TRANSACTION['status'] ) ] ? true : false, 
	        'token'         => $ENCRYPTED_DATA['oauth_token'],
	        'refresh'       => $ENCRYPTED_DATA['oauth_token_secret'],
	    ] )->execute();


	    //
        //	Update session
        //
        if( !empty( $_SESSION['user']['SERVICES'] ) ) {

        	array_push( $_SESSION['user']['SERVICES'], [
				'tumblr'					=> [ 'status', $USER_STATUSES[ !empty( $DATA_FIELD->status ) ? true : false ] ],
			] );

        } else {

        	$_SESSION['user']['SERVICES']	= [
				'tumblr'					=> [ 'status', $USER_STATUSES[ !empty( $DATA_FIELD->status ) ? true : false ] ],
			];

        }
        
		$_SESSION['user']['last_updated']	= date( 'Y-m-d H:i:s' );

	    return $RESPONSE->withRedirect( $this->router->pathFor( 'home' ) );

	}


	/**
	 * 	Fetch tumblr data for an existing likely user
	 *
	 * 	@param 	object 	$REQUEST
	 * 	@param 	object 	$RESPONSE 
	 * 
	 * 	@return object
	 */
	public function persistTumblr( $REQUEST, $RESPONSE ) {

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

			$this->flash->addMessage( 'error', 'Try again later' );

			return $RESPONSE->withRedirect( $this->router->pathFor( 'auth.signin' ) );

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

	        $this->flash->addMessage( 'error', 'Try again later' );

			return $RESPONSE->withRedirect( $this->router->pathFor( 'auth.signin' ) );

	    }


	    //
	    //  Fetch user_service record where
	    //  - service matches
	    //  - session matches
	    //  - is the most recent
	    //
	    $EXISTING_SERVICE  = $this->db->table( 'user_services' )
	    ->select( [ 'token', 'refresh' ] )
	    ->where( 'sid', $SERVICE['id'] )
	    ->where( 'sname', $REQUEST->getParam( 'uname' ) )
	    ->orderBy( 'created', 'desc' )->one();

	    if( empty( $EXISTING_SERVICE ) ) {

	        if( !empty( $this->settings['debug'] ) ) {

	        	$this->logger->addInfo( serialize( [ 'something is broken ' . session_id(), __FILE__, __LINE__ ] ) );

	        }

	        $this->flash->addMessage( 'error', 'Try again later' );

	        return $RESPONSE->withRedirect( $this->router->pathFor( 'auth.signup' ) );

	    }


	    //
	    //  Decrypt the data
	    //
	    $CRYPT 			= new c( $this->CONTAINER );

	    $DECRYPTED_DATA = $CRYPT->decrypt( $EXISTING_SERVICE, $this->settings['api_hash'] );

	    if( empty( $DECRYPTED_DATA ) ) {

	        if( !empty( $this->settings['debug'] ) ) {

	            $this->logger->addInfo( serialize( [ 'something is broken', __FILE__, __LINE__ ] ) );

	        }

	        $this->flash->addMessage( 'error', 'Please signup first' );

	        return $RESPONSE->withRedirect( $this->router->pathFor( 'auth.signup' ) );

	    }

	    if( !empty( $this->settings['debug'] ) ) {

            $this->logger->addInfo( serialize( [ 'decrypted_oauth_token' => $DECRYPTED_DATA['token'], 'decrypted_oauth_token_secret', $DECRYPTED_DATA['refresh'] ] ) );

        }


	    //
	    //  Proceed with service auth
	    //
	    $CLIENT = new t(
	        $this->settings['social'][0]['tumblr'][0]['server'][0]['client_id'],
	        $this->settings['social'][0]['tumblr'][0]['server'][0]['client_secret'],
	        $DECRYPTED_DATA['token'],
	        $DECRYPTED_DATA['refresh']
	    );

	    $PARSED_USER_RESPONSE  = $CLIENT->getUserInfo();


		//
        //  Fetch user
        //
        $EXISTING_USER  = $this->db->table( 'users' )
        ->select( ['id'] )
        ->where( 'uname', $PARSED_USER_RESPONSE->user->name )
        ->orderBy( 'created', 'desc' )
        ->limit( 1 )->one();

	    if( empty( $EXISTING_USER ) ) {

	    	return $RESPONSE->withRedirect( $this->router->pathFor( 'auth.signup' ) );

	    }


	    //
	    //	Update user_data
	    //
	    $user_date_updated 	= date( 'Y-m-d H:i:s' );

	    $this->db->table( 'user_data' )
	    ->update( ['modified' => $user_date_updated ] )
	    ->where( 'uid', $EXISTING_USER['id'] )
	    ->execute();

	    
	    //
        //	Update session
        //
        $_SESSION['user']	= [
        	'uid'			=> $EXISTING_USER['id'],
        	'uname'			=> $REQUEST->getParam( 'uname' ),
			'persistent'	=> !empty( $REQUEST->getParam( 'remember-me' ) ) ? true : false,
			'last_updated'	=> $user_date_updated,
		];

	    return $RESPONSE->withRedirect( $this->router->pathFor( 'home' ) );

	}


	/**
	 * 	Fetch email data for an existing likely user
	 *
	 * 	@param 	object 	$REQUEST
	 * 	@param 	object 	$RESPONSE 
	 * 
	 * 	@return object
	 */
	public function persistEmail( $REQUEST, $RESPONSE ) {

		//
	    //  Verify active service or quit
	    //
	    $SERVICE  = $this->db->table( 'services' )
	    ->select()
	    ->where( 'status', 'active' )
	    ->where( 'name', 'email' )->one();

	    if( empty( $SERVICE ) or $SERVICE['status'] != 'active' ) {

			if( !empty( $this->settings['debug'] ) ) {

				$this->logger->addInfo( serialize( [ 'access disabled service', __FILE__, __LINE__ ] ) );

			}

			$this->flash->addMessage( 'error', 'Try again later' );

			return $RESPONSE->withRedirect( $this->router->pathFor( 'auth.signin' ) );

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

	        $this->flash->addMessage( 'error', 'Try again later' );

			return $RESPONSE->withRedirect( $this->router->pathFor( 'auth.signin' ) );

	    }


	    //
	    //  Fetch user
	    //
	    $EXISTING_USER  = $this->db->table( 'user_data' )
	    ->select( ['id', 'password'] )
	    ->where( 'email', $REQUEST->getParam( 'uname' ) )
	    ->orderBy( 'modified', 'desc' )
	    ->limit( 1 )->one();

	    if( empty( $EXISTING_USER ) ) {

	    	$this->flash->addMessage( 'error', 'Invalid credentials' );

	    	return $RESPONSE->withRedirect( $this->router->pathFor( 'auth.signin' ) );

	    }


	    //
	    //  Decrypt the data
	    //
	    $CRYPT 				= new c( $this->CONTAINER );

	    $DECRYPTED_DATA 	= $CRYPT->decrypt( [ 'password' => $EXISTING_USER['password'] ], $this->settings['api_hash'] );

	    if( empty( $DECRYPTED_DATA ) or $REQUEST->getParam( 'pwd') != $DECRYPTED_DATA['password'] ) {

	    	$this->flash->addMessage( 'error', 'Invalid credentials' );

	    	return $RESPONSE->withRedirect( $this->router->pathFor( 'auth.signin' ) );

	    }


	    //
	    //	Update user_data
	    //
	    $user_date_updated 	= date( 'Y-m-d H:i:s' );

	    $this->db->table( 'user_data' )
	    ->update( ['modified' => $user_date_updated ] )
	    ->where( 'uid', $EXISTING_USER['id'] )
	    ->execute();


	    //
        //	Update session
        //
        $_SESSION['user']	= [
        	'uid'			=> $EXISTING_USER['id'],
        	'uname'			=> $REQUEST->getParam( 'uname' ),
			'persistent'	=> !empty( $REQUEST->getParam( 'remember-me' ) ) ? true : false,
			'last_updated'	=> $user_date_updated,
		];

		return $RESPONSE->withRedirect( $this->router->pathFor( 'home' ) );

	}

}