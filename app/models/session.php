<?php

namespace App\models;

class session extends file_storage {

	public function start( array $OPTIONS = [] ) {
		# @todo: the reason this is comemented out
		# - this server currently lacks a certificate
		# - once installed, re-enable
		return session_start( /*parent::initialize( $OPTIONS )*/ );

	}


	/**
	 * 	Sets a session variable 
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

		if( $name == 'user' ) {

			$_SESSION['user']			= $DATA;

		} else {

			$_SESSION['user'][ $name ]	= $DATA;

		}

		return true;

	}


	/**
	 *	Gets a session variable
	 * 
	 * 	@param 	string 	$name
	 * 
	 * 	@return string
	 */
	public function get( string $name ) {

		if( empty( $name ) ) {

			return null;

		}

		if( $name == 'user' ) {

			return !empty( $_SESSION['user'] ) ? $_SESSION['user'] : null;

		}

		return !empty( $_SESSION['user'][ $name ] ) ? $_SESSION['user'][ $name ] : null;

	}


	/**
	 *	Deletes a session variable
	 * 
	 * 	@param 	string 	$name
	 * 
	 * 	@return bool
	 */
	public function unset( string $name ) {

		if( empty( $name ) ) {

			return false;

		}

		if( $name == 'user' ) {

			unset( $_SESSION['user'] );

			session_destroy();

			unset( $_SESSION );

		} else {

			unset( $_SESSION['user'][ $name ] );

		}

		return true;

	}


	/**
	 *	Regenerates the session id
	 * 
	 * 	@return bool
	 */
	public function regenerateId() {
		
		if( empty( $_SESSION ) ) {

			return false;

		}

		$page_requests 	= self::get( 'page_requests' );

		self::set( 'page_requests', $page_requests+1 );

		if( $page_requests+1 >= 4 ) {

			self::set( 'page_requests', 0 );

			session_regenerate_id();

		}

		return true;

	}


	/**
	 *	Gets the session id
	 * 
	 * 	@return string
	 */
	public function getId() {

		return session_id();

	}


	/**
	 *	Gets the session name
	 * 
	 * 	@return string
	 */
	public function getName() {

		return session_name();

	}

}