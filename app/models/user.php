<?php

//
//	https://laravel.com/docs/5.7
//

namespace App\models;

use App\models\cookie;

use App\models\session;

use App\models\setting;

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
	 * 	@param 	object 	$SETTINGS
	 * 	@param 	object 	$CRYPT
	 * 	
	 * 	@return bool
	 */
	public function auth( array $DATA, object $SETTINGS, object $CRYPT ) {

		if( empty( $DATA ) 
			or empty( $DATA['USER'] ) 
			or empty( $SETTINGS ) 
			or empty( $CRYPT ) ) {

			return false;

		}


		//
		//	Remember me check
		//
		if( !empty( $DATA['persistent'] ) and !empty( $SETTINGS['session'][0]['ini_settings'][0]['session.use_cookies'] ) ) {

			//
			//	Build persistent string
			//
			$PERSISTENT 	= [
				'name' 		=> setting::fetchByName( 'session_name' )->value,
				'data'		=> time() . self::getId() . random_bytes( 16 ) . session::getId(),
			];


			//
			//  Encrypt the data
			//
			$ENCRYPTED_DATA	= $CRYPT->encrypt( [ 'data' => $PERSISTENT['data'] ], $SETTINGS['api_hash'] );

		    if( empty( $ENCRYPTED_DATA ) ) {

		    	return false;

		    }


		    //
			//	Update user
			//
			$DATA['USER']['cookie']	= $ENCRYPTED_DATA['data'];

			session::set( 'page_requests', 0 );

			self::updateUser( $DATA['USER'] );

		    cookie::set( $PERSISTENT['name'], $DATA['USER']['cookie'], $SETTINGS['session'][0]['ini_settings'][0]['session.cookie_lifetime'] );
			
		}

		return true;

	}


	/**
	 * 	Determines if user can be signed in
	 * 
	 * 	@return bool
	 */
	public function authenticated( $CONTAINER, $SETTINGS ) {

		//
		//	Check for session
		//
		$user_id 	= self::getId();

		if( !empty( $user_id ) ) {

			return true;

		}

		session::regenerateId();
		
		
		//
		//	Check for cookie
		//
		$session_name 	= setting::fetchByName( 'session_name' )->value;

		if( !empty( $SETTINGS['session'][0]['ini_settings'][0]['session.use_cookies'] ) and !empty( $session_name ) and !empty( $_COOKIE[ $session_name ] ) ) {

			$USER['cookie']	= unserialize( $_COOKIE[ $session_name ] );

			$USER 			= user_data::where( 'cookie', $USER['cookie'] )->where( 'sessid', session::getId() )->join( 'users', 'user_data.uid', '=', 'users.id' )->first();

			if( empty( $USER ) ) {

				return false;

			}

			session::regenerateId();

			$USER_UPDATED 	= self::updateUser( $USER->getAttributes() );

			return !empty( $USER_UPDATED );

		}


		//
		//	The user cannot be signed in
		//
		return false;

	}


	private function updateUser( $USER ) {

		if( empty( $USER ) ) return false;


		//
		//	Populate session
		//
		$SESSION_USER 	= session::get( 'user' );

		if( empty( $SESSION_USER ) ) {

			session::set( 'user', $USER );

		}


		//
		//	Update the user last updated
		//
		self::where( 'id', $USER['uid'] )
			->update( [ 'updated_at' => date( 'Y-m-d H:i:s' ) ] );


		//
		//	Update the user status
		//
		if( $USER['status'] != 'online' ) {

			user_data::where( 'uid', $USER['uid'] )
				->update( [ 'status' => 'online', 'cookie' => $USER['cookie'] ] );

		}


		//
		//	Update the user session
		//
		if( session::getId() != $USER['sessid'] ) {

			user_data::where( 'uid', $USER['uid'] )
				->update( [ 'sessid' => session::getId() ] );

		}

		return true;

	}


	public function getId() {

		return session::get( 'uid' );

	}


	/**
	 * 	Logs a user out
	 * 
	 * 	@return bool
	 */
	public function logout() {

		//
		//	Check for cookie
		//
		$session_name 	= setting::fetchByName( 'session_name' )->value;

		if( !empty( $SETTINGS['session'][0]['ini_settings'][0]['session.use_cookies'] ) and !empty( $session_name ) and !empty( cookie::get( $session_name ) ) ) {

			$USER['cookie']	= unserialize( cookie::get( $session_name ) );

			$USER_UPDATED 	= user_data::where( 'uid', self::getId() )->where( 'cookie', $USER['cookie'] )->where( 'sessid', session::getId() )->update( [ 'sessid' => '' ] );

		} else {

			$USER_UPDATED 	= user_data::where( 'uid', self::getId() )->where( 'sessid', session::getId() )->update( [ 'sessid' => '' ] );

		}

		session::unset( 'user' );

		cookie::set( $session_name, null, 0 );

		cookie::unset( $session_name );

		return !empty( $USER_UPDATED );

	}

}