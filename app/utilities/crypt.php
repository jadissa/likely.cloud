<?php

namespace App\utilities;

use App\controlers\controler;

class crypt extends controler {

	public function __invoke( $REQUEST, $RESPONSE, $NEXT ) {

		$this->view->getEnvironment()->addGlobal( 'csrf', [

			'field'	=> '
				<input type="hidden" name="' . $this->csrf->getTokenNameKey() . '" value="' . $this->csrf->getTokenName() . '">

				<input type="hidden" name="' . $this->csrf->getTokenValueKey() . '" value="' . $this->csrf->getTokenValue() . '">
			',

		] );

		return $NEXT( $REQUEST, $RESPONSE );
	}
	

	/**
	 * 	Encrypt request
	 *
	 *	@param 	array 	$REQUEST
	 * 	@param 	string 	$key
	 *
	 * 	@return array
	 */
	public function encrypt( $REQUEST, $key ) {

		$enc_method	= 'AES-128-CTR';

	    $enc_key	= openssl_digest( $this->settings['salt'] . ':' . $this->settings['api_hash'] . '|' . $key, 'SHA256', TRUE );

	    $enc_iv		= openssl_random_pseudo_bytes( openssl_cipher_iv_length( $enc_method ) );

	    foreach( $REQUEST as $field => $value ) {

	    	$REQUEST[ $field ]	= openssl_encrypt( $value, $enc_method, $enc_key, 0, $enc_iv ) . '::' . bin2hex( $enc_iv );

	    }

		return $REQUEST;

	}


	/**
	 * 	Decrypt request
	 *
	 *	@param 	array 	$REQUEST
	 * 	@param 	string 	$key
	 *
	 * 	@return array
	 */
	public function decrypt( $REQUEST, $key ) {

		//
	    //  Parse the data
	    //
	    foreach( $REQUEST as $field => $value ) {

	    	preg_match( '/^(.*)::(.*)$/', $value, $PARSED_DATA );

	    	if( empty( $PARSED_DATA ) ) {

		        return false;

		    }

		    $REQUEST[ $field ]	= $PARSED_DATA;

	    }

	    
	    //
	    //  Decrypt the data
	    //
	    $enc_method 								= 'AES-128-CTR';

	    $enc_key 									= openssl_digest( $this->settings['salt'] . ':' . $this->settings['api_hash'] . '|' . $key, 'SHA256', TRUE );

	    foreach( $REQUEST as $field => $PARSED_DATA ) {

	    	list(, $encrypted_data, $enc_iv)         = $PARSED_DATA;

	    	$REQUEST[ $field ]                       = openssl_decrypt( $encrypted_data, $enc_method, $enc_key, 0, hex2bin( $enc_iv ) );

	    }

		return $REQUEST;

	}

}