<?php

//
//	https://laravel.com/docs/5.7
//

namespace App\models;

use App\models\user;

use \Illuminate\Database\Eloquent\Model;

class user_service extends Model  {

	protected $table = 'user_services';
	

	//
	//	Fields identified as updatable
	//
	protected $fillable	= [
		'uid',
		'sid',
		'sname',
		'status',
		'stype',
		'refresh',
		'token',
	];


	/**
	 * 	Gets a user_services record by id
	 * 
	 *	@param 	int 	$id
	 * 
	 * 	@return object
	 */
	public function fetchById( int $id ) {

		if( empty( $id ) ) {

			return false;

		}

		$USER_SERVICE 	=  self::where( 'sid', $id )
			->where( 'uid', user::getId() )
			->first();

		if( empty( $USER_SERVICE ) ) {

			return false;

		}

		return $USER_SERVICE;

	}


	/**
	 * 	Gets a user_services for current user
	 * 
	 * 	@return object
	 */
	public function fetchForUser( string $service_type = null ) {

		if( empty( user::getId() ) ) {

			return false;

		}

		if( !empty( $service_type ) ) {

			$USER_SERVICES 	=  self::where( 'uid', user::getId() )
			->where( 'stype', $service_type )
			->get();

		} else {

			$USER_SERVICES 	=  self::where( 'uid', user::getId() )
			->get();

		}

		if( empty( $USER_SERVICES ) ) {

			return false;

		}

		return $USER_SERVICES;

	}


	/**
	 * 	Gets a user_services record by name
	 * 
	 *	@param 	string 	$name
	 * 
	 * 	@return object
	 */
	public function fetchByName( string $name ) {

		if( empty( $name ) ) {

			return false;

		}

		$USER_SERVICE 	= self::where( 'sname', $name )
			->first();

		if( empty( $USER_SERVICE ) or empty( $USER_SERVICE->count() ) ) {

			return false;

		}

		return $USER_SERVICE;

	}


	/**
	 * 	Gets many user_services records by status
	 * 
	 *	@param 	string 	$status
	 * 
	 * 	@return object
	 */
	public function fetchByStatus( string $status ) {

		if( empty( $status ) ) {

			return false;

		}

		$USER_SERVICE 	= self::where( 'status', $status )
			->orderBy( 'created_at', 'desc' )
			->get();

		if( empty( $USER_SERVICE ) or empty( $USER_SERVICE->count() ) ) {

			return false;

		}

		return $USER_SERVICE;

	}


	/**
	 * 	Inserts a user_service record
	 * 
	 *	@param 	array 	$DATA
	 * 
	 * 	@return object
	 */
	public function insert( array $DATA ) {

		if( empty( $DATA ) ) {

			return false;

		}

		$USER_DATA = new self( [] );
		$FILLABLE_FIELDS	= $USER_DATA->getFillable();

		$INSERTION_DATA		= [];

		foreach( $FILLABLE_FIELDS as $field_name ) {

			if( empty( $DATA[ $field_name ] ) ) {

				continue;
			}

			$INSERTION_DATA[ $field_name ]	= $DATA[ $field_name ];

		}

		$USER_SERVICE 	= new self( $INSERTION_DATA );

		$USER_SERVICE->save();

		return $USER_SERVICE;

	}


	/**
	 * 	Updates a user_services record
	 * 
	 * 	@param 	int 	$id
	 *	@param 	array 	$DATA
	 * 
	 * 	@return object
	 */
	public function edit( int $id, array $DATA ) {

		if( empty( $id ) or empty( $DATA ) ) {

			return false;

		}

		$USER_SERVICE = new self( [] );
		$FILLABLE_FIELDS	= $USER_SERVICE->getFillable();

		$INSERTION_DATA		= [];

		foreach( $FILLABLE_FIELDS as $field_name ) {

			if( empty( $DATA[ $field_name ] ) ) {

				continue;
			}

			$INSERTION_DATA[ $field_name ]	= $DATA[ $field_name ];

		}

		return self::where( 'sid', $id )
			->where( 'uid', user::getId() )
            ->update( $INSERTION_DATA );

	}

}