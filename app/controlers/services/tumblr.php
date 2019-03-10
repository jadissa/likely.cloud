<?php

namespace App\controlers\services;

use App\controlers\controler;

use Respect\Validation\Validator as v;

use App\utilities\crypt;

use App\models\setting;

use App\models\service;

use App\models\user;

use App\models\user_data;

use App\models\user_service;

use App\models\transaction;

use Tumblr\API\Client as tumblr_client;

class tumblr extends controler {

	/**
	 * 	Submits an tumblr login
	 *
	 *	@param 	object 	$REQUEST
	 * 	@param 	object 	$RESPONSE
	 *
	 * 	@return object
	 */
	public function login( $REQUEST, $RESPONSE ) {

		//
		//	Validate request
		//
		$VALIDATION		= $this->validator->validate( $REQUEST, [
			'service'	=> v::noWhitespace()->notEmpty(),
			'uname'		=> v::noWhitespace()->notEmpty()->alpha(),
			'pwd'		=> v::noWhitespace()->notEmpty(),
		] );

		if( $VALIDATION->failed() ) {

			return $RESPONSE->withRedirect( $this->router->pathFor( 'login' ) );

		}


		//
		//	Parse request
		//
		$PARSED_REQUEST 	= $REQUEST->getParsedBody();

		$FORMATTED_REQUEST 	= $PARSED_REQUEST;

		if( !empty( $FORMATTED_REQUEST['pwd'] ) )   $FORMATTED_REQUEST['pwd']   = 'withheld';

		if( !empty( $this->settings['debug'] ) and !empty( $PARSED_REQUEST ) ) {

			$this->logger->addInfo( serialize( $FORMATTED_REQUEST ) );

		}


		//
		//	Verify active service or quit
		//
		$SERVICE 	= service::fetchByName( $REQUEST->getParam( 'service' ) );

		if( empty( $SERVICE ) or $SERVICE['status'] != 'active' ) {

			if( !empty( $this->settings['debug'] ) ) {

				$this->logger->addInfo( serialize( [ 'access disabled service', __FILE__, __LINE__ ] ) );

			}

			$this->flash->addMessage( 'error', 'Try again later' );

			return $RESPONSE->withRedirect( $this->router->pathFor( 'login' ) );

	    }


		//
	    //  Fetch API settings or quit
	    //
		$API_SETTING  = setting::fetchByName( 'api_key' );

		if( empty( $API_SETTING ) ) {

			if( !empty( $this->settings['debug'] ) ) {

				$this->logger->addInfo( serialize( [ 'access disabled service', __FILE__, __LINE__ ] ) );

			}

	        $this->flash->addMessage( 'error', 'Try again later' );

			return $RESPONSE->withRedirect( $this->router->pathFor( 'login' ) );

	    }


	    //
	    //	Fetch the user or quit
	    //
	    $USER 	= user::fetchByUsername( $PARSED_REQUEST['uname'] );

	    if( empty( $USER ) ) {

	    	$this->flash->addMessage( 'error', 'That information seems incorrect' );

	    	return $RESPONSE->withRedirect( $this->router->pathFor( 'login' ) );

	    }


	    //
	    //	Finish validation
	    //
	    $CRYPT 	= new crypt( $this->CONTAINER );

	    $DECRYPTED_DATA	= $CRYPT->decrypt( [ 'pwd' => $USER->password ], $this->settings['api_hash'] );

	    if( empty( $DECRYPTED_DATA ) ) {

	    	$this->logger->addInfo( serialize( [ 'something is broken', __FILE__, __LINE__ ] ) );

	    	$this->flash->addMessage( 'error', 'Try again later' );

			return $RESPONSE->withRedirect( $this->router->pathFor( 'login' ) );

	    }

	    if( $PARSED_REQUEST['pwd'] != $DECRYPTED_DATA['pwd'] ) {

	    	$this->logger->addInfo( serialize( [ 'non-matching credentials', __FILE__, __LINE__ ] ) );

	    	$this->flash->addMessage( 'error', 'That information seems incorrect' );

	    	return $RESPONSE->withRedirect( $this->router->pathFor( 'login' ) );

	    }


        //
		//	Authenticate user
		//
		$AUTHENTICATED 	= user::session( [
			'USER'			=> ( array ) $USER->getAttributes(),
			'persistent'	=> !empty( $PARSED_REQUEST['remember-me'] ) ? true : false,
			'settings' 		=> $this->settings, 
			'logger' 		=> $this->logger,
			'crypt'			=> new crypt( $this->CONTAINER ),
		 ] );

	    $this->flash->addMessage( 'info', 'Yay you\'ve returned!' );

        return $RESPONSE->withRedirect( $this->router->pathFor( 'home' ) );

	}


	/**
	 * 	Submits an tumblr registry
	 *
	 *	@param 	object 	$REQUEST
	 * 	@param 	object 	$RESPONSE
	 *
	 * 	@return object
	 */
	public function register( $REQUEST, $RESPONSE ) {

		//
		//	Validate request
		//
		$VALIDATION 	= $this->validator->validate( $REQUEST, [
			'service'	=> v::noWhitespace()->notEmpty(),
			'uname'		=> v::noWhitespace()->notEmpty()->alpha(),
			'pwd'		=> v::noWhitespace()->notEmpty(),
		] );

		if( $VALIDATION->failed() ) {

			return $RESPONSE->withRedirect( $this->router->pathFor( 'register' ) );

		}


		//
		//	Parse request
		//
		$PARSED_REQUEST = $REQUEST->getParsedBody();

	    if( !empty( $this->settings['debug'] ) ) {

	        $this->logger->addInfo( serialize( $PARSED_REQUEST ) );

	    }


	    //
		//	Verify active service or quit
		//
		$SERVICE 	= service::fetchByName( $REQUEST->getParam( 'service' ) );

		if( empty( $SERVICE ) or $SERVICE['status'] != 'active' ) {

			if( !empty( $this->settings['debug'] ) ) {

				$this->logger->addInfo( serialize( [ 'access disabled service', __FILE__, __LINE__ ] ) );

			}

			$this->flash->addMessage( 'error', 'Try again later' );

			return $RESPONSE->withRedirect( $this->router->pathFor( 'register' ) );

	    }


		//
	    //  Fetch API settings or quit
	    //
		$API_SETTING  = setting::fetchByName( 'api_key' );

		if( empty( $API_SETTING ) ) {

			if( !empty( $this->settings['debug'] ) ) {

				$this->logger->addInfo( serialize( [ 'access disabled service', __FILE__, __LINE__ ] ) );

			}

	        $this->flash->addMessage( 'error', 'Try again later' );

			return $RESPONSE->withRedirect( $this->router->pathFor( 'register' ) );

	    }


	    //
	    //  Get the user's permission
	    //
	    $CLIENT = new tumblr_client(
	        $this->settings['social'][0]['tumblr'][0]['server'][0]['client_id'],
	        $this->settings['social'][0]['tumblr'][0]['server'][0]['client_secret']
	    );

	    $requestHandler = $CLIENT->getRequestHandler();

	    $requestHandler->setBaseUrl( 'https://www.tumblr.com/' );

	    $resp = $requestHandler->request( 'POST', 'oauth/request_token', [
	        'oauth_callback' => ( !empty( $this->settings['using_https'] ) ? 'https://' : 'http://' )
            . $this->settings['domain'] . $this->router->pathFor( 'service.tumblr.callback' ),
	    ] ) ;

	    parse_str( $resp->body, $PARSED_TOKEN_RESPONSE );

	    if( !empty( $this->settings['debug'] ) ) {

			$this->logger->addInfo( serialize( [ $PARSED_TOKEN_RESPONSE, __FILE__, __LINE__ ] ) );

		}


		//
	    //  Encrypt the data
	    //
	    $password 		= md5( uniqid( mt_rand() ) );

	    $CRYPT 			= new crypt( $this->CONTAINER );

	    $ENCRYPTED_DATA	= $CRYPT->encrypt( [ 
	    	'pwd'					=> $PARSED_REQUEST['pwd'],
	    	'oauth_token' 			=> $PARSED_TOKEN_RESPONSE['oauth_token'],
	    	'oauth_token_secret' 	=> $PARSED_TOKEN_RESPONSE['oauth_token_secret'], 
	    ], $this->settings['api_hash'] );

	    if( !empty( $this->settings['debug'] ) ) {

			$this->logger->addInfo( serialize( [ $ENCRYPTED_DATA, __FILE__, __LINE__ ] ) );

		}


	    //
	    //  Append transaction
	    //
	    $SERVICE_STATUSES  = [
            false   => 'invisible',
            true    => 'public',
        ];

	    $transaction_data               = json_encode( [
	        'status'                    => $SERVICE_STATUSES[ !empty( $REQUEST->getParam( 'status' ) ) ? true : false ],
	        'remember_me'				=> $REQUEST->getParam( 'remember-me' ),
	        'uname'						=> $REQUEST->getParam( 'uname' ),
	        'pwd'						=> $ENCRYPTED_DATA['pwd'],
	    ] );

	    transaction::insert( [
	        'sid'                       => $SERVICE->id,
	        'session_id'                => session_id(), 
	        'oauth_token'               => $ENCRYPTED_DATA['oauth_token'], 
	        'oauth_token_secret'        => $ENCRYPTED_DATA['oauth_token_secret'],
	        'data'                      => $transaction_data,
	    ] );

	    header( 'Location: ' . 'https://www.tumblr.com/oauth/authorize?oauth_token=' . $PARSED_TOKEN_RESPONSE['oauth_token'] );

	    return $RESPONSE;

	}


	/**
	 * 	Callback for tumblr registry
	 *
	 *	@param 	object 	$REQUEST
	 * 	@param 	object 	$RESPONSE
	 *
	 * 	@return object
	 */
	public function callback( $REQUEST, $RESPONSE ) {

		//
		//	Validate request
		//
		$VALIDATION		= $this->validator->validate( $REQUEST, [
			'oauth_token'		=> v::noWhitespace()->notEmpty(),
			'oauth_verifier'	=> v::noWhitespace()->notEmpty(),
		] );

		if( $VALIDATION->failed() ) {

			$this->flash->addMessage( 'error', 'Try again later' );

			return $RESPONSE->withRedirect( $this->router->pathFor( 'register' ) );

		}


		//
		//	Parse request
		//
		$PARSED_REQUEST 	= $REQUEST->getParsedBody();

		$FORMATTED_REQUEST 	= $PARSED_REQUEST;

		if( !empty( $FORMATTED_REQUEST['pwd'] ) )   $FORMATTED_REQUEST['pwd']   = 'withheld';

		if( !empty( $this->settings['debug'] ) and !empty( $PARSED_REQUEST ) ) {

			$this->logger->addInfo( serialize( $FORMATTED_REQUEST ) );

		}


		//
		//	Verify active service or quit
		//
		$SERVICE 	= service::fetchByName( 'tumblr' );

		if( empty( $SERVICE ) or $SERVICE['status'] != 'active' ) {

			if( !empty( $this->settings['debug'] ) ) {

				$this->logger->addInfo( serialize( [ 'access disabled service', __FILE__, __LINE__ ] ) );

			}

			$this->flash->addMessage( 'error', 'Try again later' );

			return $RESPONSE->withRedirect( $this->router->pathFor( 'register' ) );

	    }


		//
	    //  Fetch API settings or quit
	    //
		$API_SETTING  = setting::fetchByName( 'api_key' );

		if( empty( $API_SETTING ) ) {

			if( !empty( $this->settings['debug'] ) ) {

				$this->logger->addInfo( serialize( [ 'access disabled service', __FILE__, __LINE__ ] ) );

			}

	        $this->flash->addMessage( 'error', 'Try again later' );

			return $RESPONSE->withRedirect( $this->router->pathFor( 'register' ) );

	    }


	    //
	    //  Fetch transaction
	    // 
	   $EXISTING_TRANSACTION  = transaction::fetchByService( $SERVICE->id );

	    if( empty( $EXISTING_TRANSACTION ) ) {

	        if( !empty( $this->settings['debug'] ) ) {

	        	$this->logger->addInfo( serialize( [ 'something is broken ' . session_id(), __FILE__, __LINE__ ] ) );

	        }

	        $this->flash->addMessage( 'error', 'Try again later' );

	        return $RESPONSE->withRedirect( $this->router->pathFor( 'register' ) );

	    }


	    //
	    //	Parse data field
	    //
	    if( empty( $EXISTING_TRANSACTION->data ) ) {

	    	if( !empty( $this->settings['debug'] ) ) {

	        	$this->logger->addInfo( serialize( [ 'something is broken ' . session_id(), __FILE__, __LINE__ ] ) );

	        }

	        $this->flash->addMessage( 'error', 'Try again later' );

	        return $RESPONSE->withRedirect( $this->router->pathFor( 'register' ) );

	    }

	    $DATA_FIELD	= json_decode( $EXISTING_TRANSACTION->data );

	    unset( $EXISTING_TRANSACTION->data );


	    //
	    //  Decrypt the data
	    //
	    $CRYPT 			= new crypt( $this->CONTAINER );

	    $DECRYPTED_DATA = $CRYPT->decrypt( [ 
	    	'oauth_token' 			=> $EXISTING_TRANSACTION->oauth_token,
	    	'oauth_token_secret' 	=> $EXISTING_TRANSACTION->oauth_token_secret, 
	    ], $this->settings['api_hash'] );

	    if( empty( $DECRYPTED_DATA ) ) {

	        if( !empty( $this->settings['debug'] ) ) {

	            $this->logger->addInfo( serialize( [ 'something is broken', __FILE__, __LINE__ ] ) );

	        }

	        $this->flash->addMessage( 'error', 'Try again later' );

	        return $RESPONSE->withRedirect( $this->router->pathFor( 'register' ) );

	    }


	    //
	    //  Confirm data validity
	    //
	    if( $DECRYPTED_DATA['oauth_token'] != $REQUEST->getParam( 'oauth_token' ) ) {

	        if( !empty( $this->settings['debug'] ) ) {

	            $this->logger->addInfo( serialize( [ 'something is broken', __FILE__, __LINE__ ] ) );

	        }

	        $this->flash->addMessage( 'error', 'Try again later' );

	        return $RESPONSE->withRedirect( $this->router->pathFor( 'register' ) );

	    }


	    //
	    //  Proceed with service auth
	    //
	    $CLIENT = new tumblr_client(
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

	    $CLIENT = new tumblr_client(
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

	        return $RESPONSE->withRedirect( $this->router->pathFor( 'register' ) );

	    }

	    if( !empty( $this->settings['debug'] ) ) {

	        $this->logger->addInfo( serialize( $PARSED_USER_RESPONSE ) );

	    }


	    //
        //  Capture geo
        //
        $GEO = unserialize( file_get_contents( 'http://www.geoplugin.net/php.gp?ip=' . $_SERVER['REMOTE_ADDR'] ) );


        //
        //  Append user
        //
        $USER 	= user::insert( $DATA_FIELD->uname );

        if( empty( $USER->id ) ) {

			if( !empty( $this->settings['debug'] ) ) {

				$this->logger->addInfo( serialize( [ 'something is broken', __FILE__, __LINE__ ] ) );

			}

            $this->flash->addMessage( 'error', 'Try again later' );

			return $RESPONSE->withRedirect( $this->router->pathFor( 'register' ) );

        }


	    //
        //	Append data
        //
        $USER_DATA	= user_data::insert( [
            'uid'   	=> $USER->id, 
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
            'password'  => $DATA_FIELD->pwd,
            'status'    => 'online',
        ] );

        if( empty( $USER_DATA->id ) ) {

			if( !empty( $this->settings['debug'] ) ) {

				$this->logger->addInfo( serialize( [ 'something is broken', __FILE__, __LINE__ ] ) );

			}

            $this->flash->addMessage( 'error', 'Try again later' );

			return $RESPONSE->withRedirect( $this->router->pathFor( 'register' ) );

        }


        //
	    //  Encrypt tokens
	    //
	    $CRYPT 			= new crypt( $this->CONTAINER );

	    $ENCRYPTED_DATA	= $CRYPT->encrypt( [ 
	    	'oauth_token' 			=> $PARSED_REQUEST_TOKENS_RESPONSE['oauth_token'],
	    	'oauth_token_secret' 	=> $PARSED_REQUEST_TOKENS_RESPONSE['oauth_token_secret'],
	    ], $this->settings['api_hash'] );


        //
        //  Append service
        //
        $USER_SERVICE 	= user_service::insert( [
            'uid'       => $USER->id, 
            'sid'       => $SERVICE->id,
            'sname'     => $DATA_FIELD->uname, 
            'status'    => $DATA_FIELD->status, 
            'token'     => $ENCRYPTED_DATA['oauth_token'],
	        'refresh'   => $ENCRYPTED_DATA['oauth_token_secret'],
        ] );


        if( empty( $USER_SERVICE->id ) ) {

			if( !empty( $this->settings['debug'] ) ) {

				$this->logger->addInfo( serialize( [ 'access disabled service', __FILE__, __LINE__ ] ) );

			}

            $this->flash->addMessage( 'error', 'Try again later' );

			return $RESPONSE->withRedirect( $this->router->pathFor( 'register' ) );

        }


        //
		//	Authenticate user
		//
		$AUTHENTICATED 	= user::session( [
			'USER'			=> ( array ) $USER->getAttributes(),
			'persistent'	=> !empty( $PARSED_REQUEST['remember-me'] ) ? true : false,
			'settings' 		=> $this->settings, 
			'logger' 		=> $this->logger,
			'crypt'			=> new crypt( $this->CONTAINER ),
		 ] );

		$this->flash->addMessage( 'info', 'Yay for registering!' );

        return $RESPONSE->withRedirect( $this->router->pathFor( 'home' ) );

	}

}