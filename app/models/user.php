<?php

//
//	https://laravel.com/docs/5.7
//

namespace App\models;

use App\models\cookie;

use App\models\session;

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

		$USER 	= self::select('users.*', 'user_services.*', 'user_data.*' )
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
	 *	@param 	string 	$context (online, offline)
	 *
	 * 	@return array
	 */
	public function fetchUsers( $context = 'online' ) {

		$USERS 	= self::select( 'users.id', 'users.uname', 'user_services.status as user_service_status', 'user_services.stype', 'user_data.status as user_data_status', 'user_data.updated_at' )
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
	 * 	Updates a user username
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
	 *	@param 	int 	$id
	 *	@param 	string 	$session
	 * 
	 * 	@return object
	 */
	public function updateSession( int $id, string $session ) {

		if( empty( $id ) or empty( $session ) ) {

			return false;

		}

		return user_data::where( 'uid', $id )
            ->update( [ 'sessid' => $session ] );

	}


	/**
	 * 	Gets user session
	 * 
	 *	@param 	int 	$id
	 *	@param 	string 	$session
	 * 
	 * 	@return object
	 */
	public function fetchSession( string $session ) {

		if( empty( $id ) or empty( $session ) ) {

			return false;

		}

		return user_data::where( 'uid', $id )
            ->where( [ 'sessid' => $session ] )
            ->get();

	}


	/**
	 * 	Updates a user session
	 * 	
	 * 	@param 	array 	$DATA 
	 * 	
	 * 	@return bool
	 */
	public function session( array $DATA ) {

		if( empty( $DATA ) ) {

			return false;

		}


		//
		//	Set session
		//
		$USER_SESSION_DATA 	= [
			'uid'			=> $DATA['USER']['uid'],
			'uname'			=> $DATA['USER']['uname'],
			'status' 		=> $DATA['USER']['status'],
			'using_cookie'	=> false,
			'page_requests'	=> 0,
		];

		return session::set( 'user', $USER_SESSION_DATA );

		/*
		//
		//	Remember me check
		//
		if( !empty( $DATA['persistent'] ) and !empty( $DATA['settings']['session'][0]['use_cookies'] ) ) {

			//
			//	Build hash
			//
			$persistent_string 	= time() . $DATA['USER']['uid'] . random_bytes( 16 ) . session::getId();


			//
			//  Encrypt data
			//
			$ENCRYPTED_DATA	= $DATA['crypt']->encrypt( [ 'value' => $persistent_string ], $DATA['settings']['api_hash'] );

		    if( empty( $ENCRYPTED_DATA ) ) {

		    	return false;

		    }


		    //
			//	Set session
			//
			$USER_SESSION_DATA 	= [
				'uid'			=> $DATA['USER']['uid'],
				'uname'			=> $DATA['USER']['uname'],
				'status' 		=> $DATA['USER']['status'],
				'using_cookie'	=> true,
				'page_requests'	=> 0,
			];

			$UPDATED_SESSION	= session::set( 'user', $USER_SESSION_DATA );

			if( empty( $UPDATED_SESSION ) ) return false;


			//
			//	Set cookie
			//
			$DATA['USER']['cookie']	= $ENCRYPTED_DATA['value'];

			$UPDATED_COOKIE 	= cookie::set(
				$DATA['settings']['session'][0]['name'], 
				$ENCRYPTED_DATA['value']
			);

			if( empty( $UPDATED_COOKIE ) ) return false;
			
		} else {

			//
			//	Set session
			//
			$USER_SESSION_DATA 	= [
				'uid'			=> $DATA['USER']['uid'],
				'uname'			=> $DATA['USER']['uname'],
				'status' 		=> $DATA['USER']['status'],
				'using_cookie'	=> false,
				'page_requests'	=> 0,
			];

			$UPDATED_SESSION	= session::set( 'user', $USER_SESSION_DATA );

			if( empty( $UPDATED_SESSION ) ) return false;

		}

		//
		//	Update user
		//
		$UPDATED_USER 	= self::updateSession( $DATA );
		*/

	}


	/**
	 * 	Determines if user authenticated
	 * 	
	 * 	@param 	array 	$DATA 
	 * 
	 * 	@return bool
	 */
	public function authenticated( array $DATA ) {

		if( empty( session::get( 'user' ) ) ) {

			if( $USER = self::fetchSession( session::getId() ) ) {

				if( empty( auth( $USER ) ) ) {

					return false;

				}

			} else {

				return false;

			}

		}


		//
		//	Update session
		//
		session::regenerateId();
		

		//
		//	Ping record
		//
		self::updateSession( user::getId(), session::getId() );

		return true;

		/*
		//
		//	Check cookie
		//
		if( !empty( session::get( 'user')['using_cookie'] ) 
			and !empty( cookie::get( session::getName() ) ) ) {

			//
			//	Get user where
			//	- the current session matches
			//	- the session is not empty
			//	- the cookie matches
			//
			# @todo: check out best practices for looking up a user via cookie to be sure we are doing this right
			$DATA['USER'] 			= user_data::where( 'cookie', cookie::get( session::getName() ) )
				->where( 'sessid', session::getId() )
				->where( 'sessid', '<>', '' )
				->join( 'users', 'user_data.uid', '=', 'users.id' )
				->first();

			if( empty( $DATA['USER'] ) ) {

				$DATA['logger']->addInfo( serialize( [ 'could not find user', __LINE__ ] ) );

				return false;

			}

			var_dump( $DATA['USER'], 'here' );exit;

		} else {

			$DATA['USER'] 			= user_data::where( 'sessid', session::getId() )
				->join( 'users', 'user_data.uid', '=', 'users.id' )
				->first();

			if( empty( $DATA['USER'] ) ) {

				$DATA['logger']->addInfo( serialize( [ 'could not find user', __LINE__ ] ) );

				return false;

			}

		}


		//
		//	Update session
		//
		session::regenerateId();


		//
		//	Update user
		//
		$USER_UPDATED 	= self::updateSession( $DATA );

		if( empty( $USER_UPDATED ) ) {

			$DATA['logger']->addInfo( serialize( [ 'could not update user', __LINE__ ] ) );

			return false;

		}

		$DATA['logger']->addInfo( serialize( [ 'successfully authenticated' ] ) );


		//
		//	Success
		//
		return true;
		*/

	}


	/**
	 * 	Updates authenticated user session
	 * 	
	 * 	@param 	array 	$DATA 
	 * 
	 * 	@return bool
	 */
	/*
	private function updateSession( $DATA  ) {

		if( empty( $DATA ) ) return false;


		//
		//	Populate session
		//
		$SESSION_USER 	= session::get( 'user' );

		if( empty( $SESSION_USER ) ) return false;


		//
		//	Get user record
		//
		$USER_DATA 	= user_data::where( 'uid', user::getId() )->get();

		if( empty( $USER_DATA ) ) return false;


		//
		//	Check for cookie
		//
		if( !empty( session::get( 'user')['using_cookie'] ) 
			and !empty( cookie::get( $DATA['settings']['session'][0]['name'] ) ) ) {

			//
			//	Update user
			//
			$UPDATED_USER 	= $USER_DATA->each( function( $USER_DATA ) use( $DATA ) {

				$USER_DATA->status 		= session::get( 'user')['status'];

				$USER_DATA->sessid 		= session::getId();

				$USER_DATA->cookie 	= $DATA['USER']['cookie'];

				$USER_DATA->updated_at 	= time();

				return $USER_DATA->save();

			} );
			
			if( empty( $UPDATED_USER ) ) return false;

		} else {

			//
			//	Update user
			//
			$UPDATED_USER	= $USER_DATA->each( function( $USER_DATA ) {

				$USER_DATA->status 		= session::get( 'user')['status'];

				$USER_DATA->sessid 		= session::getId();

				$USER_DATA->updated_at 	= time();

				return $USER_DATA->save();

			} );

			if( empty( $UPDATED_USER ) ) return false;

		}

		return true;

	}
	*/


	/**
	 * 	Logs authenticated user out
	 *
	 * 	@param 	array 	$DATA 
	 * 
	 * 	@return bool
	 */
	public function logout( $DATA ) {

		/*
		//
		//	Check for cookie
		//
		if( !empty( $SETTINGS['session'][0]['use_cookies'] ) 
			and !empty( cookie::get( $DATA['settings']['session'][0]['name'] ) ) ) {

			$USER['cookie']	= cookie::get( $DATA['settings']['session'][0]['name'] );

			$USER_UPDATED 	= user_data::where( 'uid', session::getId() )
				->where( 'cookie', $USER['cookie'] )
				->where( 'sessid', session::getId() )
				->update( [ 'sessid' => '' ] );

		} else {
			$USER_UPDATED 	= user_data::where( 'uid', session::getId() )
			->where( 'sessid', session::getId() )
			->update( [ 'sessid' => '' ] );

		}
		*/

		return session::unset( 'user' );

		/*
		cookie::set(
			session::getName(), 
			''
		);

		cookie::unset( session::getName() );

		return !empty( $USER_UPDATED );
		*/

	}


	public function getId() {

		return !empty( session::get( 'user' ) ) ? session::get( 'user' )['uid'] : 0;

	}

}