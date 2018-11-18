<?php

namespace App\controlers;

use Respect\Validation\Validator as v;

use Tumblr\API\Client as t;

class services extends controler {

	public function getActive( $REQUEST, $RESPONSE ) {

		$SERVICES  = $this->db->table( 'services' )
    	->select()
    	->where( 'status', 'active' )->get();

    	return $SERVICES;

	}


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
	        'oauth_callback' => $this->router->pathFor( 'service.tumblr' ),
	    ] ) ;

	    parse_str( $resp->body, $PARSED_TOKEN_RESPONSE );


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

	    #return $RESPONSE->withStatus(302)->withHeader('Location', 'https://www.tumblr.com/oauth/authorize?oauth_token=' . $PARSED_TOKEN_RESPONSE['oauth_token'] );

	    #$RESPONSE->withRedirect( 'https://www.tumblr.com/oauth/authorize?oauth_token=' . $PARSED_TOKEN_RESPONSE['oauth_token'], 302);

	    header( 'Location: ' . 'https://www.tumblr.com/oauth/authorize?oauth_token=' . $PARSED_TOKEN_RESPONSE['oauth_token'] );

	    return $RESPONSE;

	}


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
		//	Parse request
		//
		$PARSED_REQUEST = $REQUEST->getParsedBody();

	    if( !empty( $this->settings['debug'] ) ) {

	        $this->logger->addInfo( serialize( $PARSED_REQUEST ) );

	    }

	    return $RESPONSE;

	}

}