<?php

namespace App\controlers\auth;

use App\controlers\controler;

use Respect\Validation\Validator as v;

use App\controlers\listed\feed as feed;

class auth extends controler {

	/**
	 * 	Renders the signup view
	 *
	 *	@param 	object 	$REQUEST
	 * 	@param 	object 	$RESPONSE
	 *	@param 	object 	$ARGS
	 *
	 * 	@return bool
	 */
	public function getSignup( $REQUEST, $RESPONSE, $ARGS ) {

		//
		//	Fetch active service
		//
		$ACTIVE_SERVICES	= $this->{'services'}->getActive( $REQUEST, $RESPONSE );


		//
		//	Setup view
		//
		$FEED 		= new feed( $this->CONTAINER );

		$FEED_DATA 	= $FEED->getFeed( $REQUEST, $RESPONSE );

		$this->view->getEnvironment()->addGlobal( 'SERVICE_REGISTRIES', $FEED_DATA['SERVICE_REGISTRIES'] );

		$this->view->getEnvironment()->addGlobal( 'SERVICES', $ACTIVE_SERVICES );

		if( empty( $ACTIVE_SERVICES ) ) {

			$this->view->getEnvironment()->addGlobal( 'theme_disabled', true );

		}

		return $this->view->render( $RESPONSE, 'auth/signup.twig' );

	}


	/**
	 * 	Submits the signup view
	 *
	 *	@param 	object 	$REQUEST
	 * 	@param 	object 	$RESPONSE
	 *
	 * 	@return bool
	 */
	public function postSignup( $REQUEST, $RESPONSE ) {

		//
		//	Check for redirect
		//
		switch( $REQUEST->getParam( 'service' ) ) {

			case 'tumblr':

				return $RESPONSE->withStatus(302)->withHeader('Location', $this->router->pathFor( 'service.tumblr', [], $REQUEST->getParsedBody() ) );

				#return $RESPONSE->withRedirect( $this->router->pathFor( 'service.tumblr' ), $REQUEST );

			break;

			default:

			break;

		}


		//
		//	Validate request
		//
		$VALIDATION		= $this->validator->validate( $REQUEST, [
			'email'	=> v::noWhitespace()->notEmpty()->email(),
			'uname'	=> v::noWhitespace()->notEmpty()->alpha(),
			'pwd'	=> v::noWhitespace()->notEmpty(),
		] );

		if( $VALIDATION->failed() ) {

			return $RESPONSE->withRedirect( $this->router->pathFor( 'auth.signup' ) );

		}


		//
		//	Parse request
		//
		$PARSED_REQUEST = $REQUEST->getParsedBody();

		$FORMATTED_REQUEST      = $PARSED_REQUEST;

		if( !empty( $FORMATTED_REQUEST['pwd'] ) )   $FORMATTED_REQUEST['pwd']   = 'withheld';

		if( !empty( $this->settings['debug'] ) and !empty( $PARSED_REQUEST ) ) {

			$this->logger->addInfo( serialize( $FORMATTED_REQUEST ) );

		}


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

				$this->logger->addInfo( serialize( [ 'access disabled service', __FILE__, __LINE__ ] ) );

			}

	        $_SESSION['ERRORS']['failed']	= 'Try again later';

			return $RESPONSE->withRedirect( $this->router->pathFor( 'auth.signup' ) );

	    }


		//
		//  Encrypt the data
		//
		$enc_method         = 'AES-128-CTR';

		$enc_key            = openssl_digest( $this->settings['salt'] . ':' . $this->settings['api_hash'] . '|' . $API_SETTINGS['value'], 'SHA256', TRUE );

		$enc_iv             = openssl_random_pseudo_bytes( openssl_cipher_iv_length( $enc_method ) );

		$encrypted_password = openssl_encrypt( $PARSED_REQUEST['pwd'], $enc_method, $enc_key, 0, $enc_iv ) . '::' . bin2hex( $enc_iv );


	    //
	    //  Fetch user
	    //
	    $EXISTING_USER  = $this->db->table( 'users' )
	    ->select( ['id'] )
	    ->where( 'uname', $PARSED_REQUEST['uname'] )
	    ->orderBy( 'created', 'desc' )
	    ->limit( 1 )->one();

	    if( !empty( $EXISTING_USER ) ) {

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
            'uname'     => $PARSED_REQUEST['uname'],
        ] )->execute();


        //
        //  Fetch user_id
        //
        $EXISTING_USER  = $this->db->table( 'users' )
        ->select( ['id'] )
        ->where( 'uname', $PARSED_REQUEST['uname'] )
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
        //  Append data
        //
        $this->db->table('user_data')->insert( [
            'uid'   => $user_id, 
            'email'	=> $PARSED_REQUEST['email'],
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
        
        $this->db->table('user_services')->insert( [
            'uid'       => $user_id, 
            'sid'       => $SERVICE['id'],
            'sname'     => $PARSED_REQUEST['uname'], 
            'created'   => date( 'Y-m-d H:i:s' ), 
            'login'     => date( 'Y-m-d H:i:s' ), 
            'status'    => $USER_STATUSES[ !empty( $PARSED_REQUEST['status'] ) ? true : false ], 
        ] )->execute();


        //
        //  Fetch user_services_id
        //
        $EXISTING_USER_SERVICE  = $this->db->table( 'user_services' )
        ->select( ['id'] )
        ->where( 'uid', $user_id )
        ->limit( 1 )->one();

        $service_id = $EXISTING_USER_SERVICE['id'];

        if( empty( $service_id ) ) {

			if( !empty( $this->settings['debug'] ) ) {

				$this->logger->addInfo( serialize( [ 'access disabled service', __FILE__, __LINE__ ] ) );

			}

            $_SESSION['ERRORS']['failed']	= 'Try again later';

			return $RESPONSE->withRedirect( $this->router->pathFor( 'auth.signup' ) );

        }


        //
        //	Update session
        //
        $_SESSION['user']	= [
        	'uid'			=> $user_id,
        	'uname'			=> $PARSED_REQUEST['uname'],
			'persistent'	=> !empty( $PARSED_REQUEST['remember-me'] ) ? true : false,
			'SERVICES'		=> [
				'email'	=> [ 'status', $USER_STATUSES[ !empty( $PARSED_REQUEST['status'] ) ? true : false ] ],
			],
			'last_updated'	=> $user_date_created,
		];

        return $RESPONSE->withRedirect( $this->router->pathFor( 'home' ) );
        #return json_encode( [ 'stat' => true, 'redirect' => '/', 'message' => 'Registered' ] );

		# https://www.youtube.com/watch?v=49cuwfzgf-s&list=PLOfTY28w1MFlKq7fNPUlQol6Uzp3VXkBM&index=13

	}


	/**
	 * 	Renders the preferences view
	 *
	 *	@param 	object 	$REQUEST
	 * 	@param 	object 	$RESPONSE
	 *	@param 	object 	$ARGS
	 *
	 * 	@return bool
	 */
	public function getPreferences( $REQUEST, $RESPONSE, $ARGS ) {

		//
		//	Redirect check
		//
		if( empty( $_SESSION ) || empty( $_SESSION['user'] ) ) {

			return $RESPONSE->withRedirect( $this->router->pathFor( 'auth.signup' ) );
			
		}

		return $RESPONSE;

	}


	/**
	 * 	Renders the assets view
	 *
	 *	@param 	object 	$REQUEST
	 * 	@param 	object 	$RESPONSE
	 *	@param 	object 	$ARGS
	 *
	 * 	@return bool
	 */
	public function getAssets( $REQUEST, $RESPONSE, $ARGS ) {

		//
		//	Redirect check
		//
		if( empty( $_SESSION ) || empty( $_SESSION['user'] ) ) {

			return $RESPONSE->withRedirect( $this->router->pathFor( 'auth.signup' ) );
			
		}

		return $RESPONSE;

	}

}