<?php

//
//	https://laravel.com/docs/5.7
//

namespace App\models;

use \Illuminate\Database\Eloquent\Model;

class user extends Model  {

	protected $table = 'users';
	

	//
	//	Fields identified as updatable
	//
	protected $fillable	= [
		'uname',
	];


	/**
	 * 	Gets a user by id
	 * 
	 *	@param 	int 	$id
	 * 
	 * 	@return object
	 */
	public function fetchById( int $id ) {

		if( empty( $id ) ) {

			return false;

		}

		$USER 	= self::select('users.*', 'user_data.*', 'user_services.*' )
			->join( 'user_data', 'users.id', '=', 'user_data.uid' )
			->join( 'user_services', 'users.id', '=', 'user_services.uid' )
			->where( 'users.id', $id )
			->orderBy( 'users.created_at', 'desc' )
			->first();

		if( empty( $USER ) ) {

			return false;

		}

		return $USER;
	}


	/**
	 * 	Gets a user by username
	 * 
	 *	@param 	string 	$username
	 * 
	 * 	@return object
	 */
	public function fetchByUsername( string $username ) {

		if( empty( $username ) ) {

			return false;

		}

		$USER 	= self::select('users.*', 'user_data.*', 'user_services.*' )
			->join( 'user_data', 'users.id', '=', 'user_data.uid' )
			->join( 'user_services', 'users.id', '=', 'user_services.uid' )
			->where( 'users.uname', $username )
			->orderBy( 'users.created_at', 'desc' )
			->first();

		if( empty( $USER ) ) {

			return false;

		}

		return $USER;

	}


	/**
	 * 	Gets a user by email
	 * 
	 *	@param 	string 	$email
	 * 
	 * 	@return object
	 */
	public function fetchByEmail( string $email ) {

		if( empty( $email ) ) {

			return false;

		}

		$USER 	= self::select( 'users.*', 'user_data.*', 'user_services.*' )
			->join( 'user_data', 'users.id', '=', 'user_data.uid' )
			->join( 'user_services', 'users.id', '=', 'user_services.uid' )
			->where( 'user_data.email', $email )
			->orderBy( 'users.created_at', 'desc' )
			->first();

		if( empty( $USER ) ) {

			return false;

		}

		return $USER;

	}


	/**
	 * 	Gets a user's services
	 * 
	 *	@param 	int 	$id
	 * 
	 * 	@return object
	 */
	public function fetchServices( int $id ) {

		if( empty( $id ) ) {

			return false;

		}

		$USER_SERVICES 	= self::select( 'user_services.*' )
			->join( 'user_services', 'users.id', '=', 'user_services.uid' )
			->where( 'user_services.uid', $uid )
			->orderBy( 'user_services.created_at', 'desc' )
			->first();

		if( empty( $USER_SERVICES ) ) {

			return false;

		}

		return $USER_SERVICES;

	}


	/**
	 * 	Gets a user service
	 * 
	 *	@param 	int 	$id
	 * 	@param 	int 	$service_id
	 * 
	 * 	@return object
	 */
	public function fetchService( int $id, int $service_id ) {

		if( empty( $id ) or empty( $service_id ) ) {

			return false;

		}

		$USER_SERVICE 	= self::select( 'user_services.*' )
			->join( 'user_services', 'users.id', '=', 'user_services.uid' )
			->where( 'user_services.sid', $service_id )
			->where( 'user_services.uid', $id )
			->orderBy( 'user_services.created_at', 'desc' )
			->first();

		if( empty( $USER_SERVICE ) ) {

			return false;

		}

		return $USER_SERVICE;

	}


	/**
	 * 	Gets most recent signups
	 * 
	 * 	@return object
	 */
	public function fetchRecentRegistries() {

		$REGISTRIES 	= self::select( 'users.created_at', 'user_data.geo', 'user_services.sname', 'services.name' )
			->where( 'user_services.status', 'public' )
			->join( 'user_data', 'users.id', '=', 'user_data.uid' )
			->join( 'user_services', 'users.id', '=', 'user_services.uid' )
			->join( 'services', 'user_services.sid', '=', 'services.id' )
			->orderBy( 'users.created_at', 'desc' )
			->limit( 20 )
			->get();

		if( empty( $REGISTRIES ) ) {

			return false;

		}

		return $REGISTRIES;

	}

	/**
	 * 	Inserts a user record
	 * 
	 *	@param 	string 	$username
	 * 
	 * 	@return object
	 */
	public function insert( string $username ) {

		if( empty( $username ) ) {

			return false;

		}

		$USER 	= new self( [ 
			'uname' => $username 
		] );

		$USER->save();

		return $USER;

	}


	/**
	 * 	Updates a user record
	 * 
	 *	@param 	int 	$id
	 *	@param 	string 	$username
	 * 
	 * 	@return object
	 */
	public function updateUsername( int $id, string $username ) {

		if( empty( $id ) or empty( $username ) ) {

			return false;

		}

		return self::where( 'id', $id )
            ->update( [ 'uname' => $username ] );

	}


	/**
	 * 	Determines if user signed in
	 * 
	 * 	@return bool
	 */
	public function authenticated() {

		return isset( $_SESSION['user']['uid'] );

	}


	/**
	 * 	Logs a user out
	 * 
	 * 	@return bool
	 */
	public function logout() {

		unset( $_SESSION['user'] );

		return true;

	}

}