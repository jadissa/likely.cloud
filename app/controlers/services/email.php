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

class email extends controler {

	/**
	 * 	Submits an email login
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
		$VALIDATION	= $this->validator->validate( $REQUEST, [
			'uname'	=> v::noWhitespace()->notEmpty()->alpha(),
			'pwd'	=> v::noWhitespace()->notEmpty(),
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

	    	$this->flash->addMessage( 'error', 'That information seems incorrect' );

	    	return $RESPONSE->withRedirect( $this->router->pathFor( 'login' ) );

	    }


	    //
	    //	Fetch user service
	    //
	    $USER_SERVICE 	= user::fetchService( $USER->id, $SERVICE->id );


		//
		//	Update session
		//
		$AUTHENTICATED 	= user::auth( [
			'USER'			=> [ 'uid' => $USER->id, 'uname' => $USER->uname ],
			'SERVICE'		=> [ 'email' => service::$SERVICE_STATUSES[ !empty( $USER_SERVICE->status ) ? true : false ] ],
			'persistent'	=> !empty( $PARSED_REQUEST['remember-me'] ) ? true : false,
		] );

		$this->flash->addMessage( 'info', 'Yay you\'ve returned!' );

        return $RESPONSE->withRedirect( $this->router->pathFor( 'home' ) );

	}

	/**
	 * 	Submits an email registry
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
		$VALIDATION	= $this->validator->validate( $REQUEST, [
			'email'	=> v::noWhitespace()->notEmpty()->email(),
			'uname'	=> v::noWhitespace()->notEmpty()->alpha(),
			'pwd'	=> v::noWhitespace()->notEmpty(),
		] );

		if( $VALIDATION->failed() ) {

			return $RESPONSE->withRedirect( $this->router->pathFor( 'register' ) );

		}

		if( !empty( user::fetchByUsername( $REQUEST->getParam( 'uname' ) ) ) or !empty( user::fetchByEmail( $REQUEST->getParam( 'email' ) ) ) ) {

			$this->flash->addMessage( 'error', 'That username or email is already registered' );

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
		//  Encrypt the data
		//
		$CRYPT 	= new crypt( $this->CONTAINER );

	    $ENCRYPTED_DATA	= $CRYPT->encrypt( [ 'pwd' => $PARSED_REQUEST['pwd'] ], $this->settings['api_hash'] );

	    if( empty( $ENCRYPTED_DATA ) ) {

	    	$this->logger->addInfo( serialize( [ 'something is broken', __FILE__, __LINE__ ] ) );

	    	$this->flash->addMessage( 'error', 'Try again later' );

			return $RESPONSE->withRedirect( $this->router->pathFor( 'register' ) );

	    }


	    //
        //  Capture geo
        //
        $GEO = unserialize( file_get_contents( 'http://www.geoplugin.net/php.gp?ip=' . $_SERVER['REMOTE_ADDR'] ) );


        //
        //  Append user
        //
        $USER 	= user::insert( $PARSED_REQUEST['uname'] );

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
            'email'		=> $PARSED_REQUEST['email'],
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
            'password'  => $ENCRYPTED_DATA['pwd'],
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
        //  Append service
        //
        $SERVICE_STATUSES  = [
            false   => 'invisible',
            true    => 'public',
        ];

        $USER_SERVICE 	= user_service::insert( [
            'uid'       => $USER->id, 
            'sid'       => $SERVICE->id,
            'sname'     => $PARSED_REQUEST['uname'], 
            'status'    => $SERVICE_STATUSES[ !empty( $PARSED_REQUEST['status'] ) ? true : false ], 
        ] );


        if( empty( $USER_SERVICE->id ) ) {

			if( !empty( $this->settings['debug'] ) ) {

				$this->logger->addInfo( serialize( [ 'access disabled service', __FILE__, __LINE__ ] ) );

			}

            $this->flash->addMessage( 'error', 'Try again later' );

			return $RESPONSE->withRedirect( $this->router->pathFor( 'register' ) );

        }

	    
	    //
        //	Update session
        //
        $_SESSION['user']	= [
        	'uid'			=> $USER->id,
        	'uname'			=> $PARSED_REQUEST['uname'],
			'persistent'	=> !empty( $PARSED_REQUEST['remember-me'] ) ? true : false,
			'SERVICES'		=> [
				'email'	=> [ 'status', $SERVICE_STATUSES[ !empty( $PARSED_REQUEST['status'] ) ? true : false ] ],
			],
		];

		$this->flash->addMessage( 'info', 'Yay for registering!' );

        return $RESPONSE->withRedirect( $this->router->pathFor( 'home' ) );

	}

}