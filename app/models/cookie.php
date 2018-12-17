<?php

namespace App\models;

class cookie extends file_storage {

	/**
	 * 	Sets a cookie variable
	 * 	
	 * 	@param 	string 	$name
	 * 	@param 	mixed 	$DATA
	 *
	 *	@return bool 
	 */
	public function set( string $name, $DATA ) {

		if( empty( $name ) ) {

			return false;

		}

		$cookie_data 	= $DATA; //!empty( $DATA ) ? serialize( $DATA ) : $DATA;

		$OPTIONS 	= parent::initialize();

		return setcookie(
			$name, 
			$cookie_data, 
			time() + $OPTIONS['cookie_lifetime'], 
			$OPTIONS['cookie_path'], 
			$OPTIONS['cookie_domain'], 
			$OPTIONS['cookie_secure'], 
			$OPTIONS['cookie_httponly']
		);

	}


	/**
	 *	Gets a cookie variable
	 * 
	 * 	@param 	string 	$name
	 * 
	 * 	@return string
	 */
	public function get( string $name ) {

		if( empty( $name ) ) {

			return null;

		}
		
		return !empty( $_COOKIE[ $name ] ) ? $_COOKIE[ $name ]/*unserialize( $_COOKIE[ $name ] )*/ : null;

	}


	/**
	 *	Deletes a cookie variable
	 * 
	 * 	@param 	string 	$name
	 * 
	 * 	@return bool
	 */
	public function unset( string $name ) {


		if( empty( $name ) ) {

			return false;

		}

		unset( $_COOKIE[ $name ] );

		return true;

	}

}