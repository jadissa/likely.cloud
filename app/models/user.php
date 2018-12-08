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
	 * 	Gets most recent public registries
	 * 
	 * 	@return object
	 */
	public function fetchRecentRegistries() {

		$REGISTRIES 	= self::select( 'users.id', 'users.created_at', 'user_data.geo', 'user_services.sname', 'services.name' )
			->where( 'user_services.status', 'public' )
			->join( 'user_data', 'users.id', '=', 'user_data.uid' )
			->join( 'user_services', 'users.id', '=', 'user_services.uid' )
			->join( 'services', 'user_services.sid', '=', 'services.id' )
			->orderBy( 'users.created_at', 'desc' )
			->limit( 20 )
			->get()->keyBy( 'id' );

		if( empty( $REGISTRIES ) ) {

			return false;

		}

		return $REGISTRIES;

	}


	/**
	 * 	Gets a list of users within context
	 *
	 *	@param 	string 	$context (online, invisible)
	 *
	 * 	@return array
	 */
	public function fetchUsers( $context = 'online' ) {

		$USERS 	= self::select( 'users.id', 'users.uname', 'users.updated_at', 'user_services.status as user_service_status', 'user_services.stype', 'user_data.status as user_data_status' )
			->where( 'user_data.status', $context )
			->join( 'user_services', 'users.id', '=', 'user_services.uid' )
			->join( 'user_data', 'users.id', '=', 'user_data.uid' )
			->orderBy( 'user_services.updated_at', 'desc' )
			->get()->keyBy( 'id' );

		if( empty( $USERS ) ) {

			return false;

		}

		return $USERS;

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
	 * 	Updates a user session
	 * 	
	 * 	@param 	array 	$DATA 
	 * 	
	 * 	@return bool
	 */
	public function auth( array $DATA = [] ) {

		if( empty( $DATA ) 
			or empty( $DATA['USER'] ) 
			or empty( $DATA['SERVICE'] ) ) {

			return false;

		}

		$_SESSION['user']	= $DATA['USER'];

		! empty( $_SESSION['SERVICES'] ) ? array_push( $_SESSION['SERVICES'], $DATA['SERVICE'] ) : $_SESSION['SERVICES'][] = $DATA['SERVICE'];

		self::where( 'id', self::getId() )
			->update( [ 'updated_at' => date( 'Y-m-d H:i:s' ) ] );

		user_data::where( 'uid', self::getId() )
			->update( [ 'status' => 'online' ] );

		return true;

	}


	/**
	 * 	Determines if user signed in
	 * 
	 * 	@return bool
	 */
	public function authenticated() {

		return isset( $_SESSION['user']['uid'] );

	}

	public function getId() {

		return !empty( self::authenticated() ) ? $_SESSION['user']['uid'] : null;
	}


	/**
	 * 	Logs a user out
	 * 
	 * 	@return bool
	 */
	public function logout() {

		unset( $_SESSION['user'] );

		session_destroy();

		return true;

	}

}