<?php

namespace App\models;

class cookie {

	/**
	 * 	Sets a cookie variable
	 * 	
	 * 	@param 	string 	$name
	 * 	@param 	mixed 	$DATA
	 * 	@param 	int 	$duration
	 *
	 *	@return bool 
	 */
	public function set( string $name, $DATA, int $duration = 86400 ) {

		if( empty( $name ) ) {

			return false;

		}

		$cookie_data 	= !empty( $DATA ) ? serialize( $DATA ) : $DATA;

		return setcookie( $name, $cookie_data, time() + $duration );

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

		return !empty( $_COOKIE[ $name ] ) ? unserialize( $_COOKIE[ $name ] ) : null;

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