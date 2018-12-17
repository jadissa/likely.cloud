<?php

//
//	https://laravel.com/docs/5.7
//

namespace App\models;

use App\models\user;
use \Illuminate\Database\Eloquent\Model;

class user_data extends Model  {

	protected $table = 'user_data';


	//
	//	Fields identified as updatable
	//
	protected $fillable	= [
		'uid',
		'status',
		'flname',
		'email',
		'geo',
		'sessid',
		'password'
	];


	/**
	 * 	Gets a user_data record by id
	 * 
	 *	@param 	int 	$id
	 * 
	 * 	@return object
	 */
	public function fetchById( int $id ) {

		if( empty( $id ) ) {

			return false;

		}

		$USER_DATA 	=  self::where( 'id', $id )
			->first();

		if( empty( $USER_DATA ) or empty( $USER_DATA->count() ) ) {

			return false;

		}

		return $USER_DATA;

	}


	/**
	 * 	Gets a user_data record by name
	 * 
	 *	@param 	string 	$name
	 * 
	 * 	@return object
	 */
	public function fetchByName( string $name ) {

		if( empty( $name ) ) {

			return false;

		}

		$USER_DATA 	= self::where( 'sname', $name )
			->first();

		if( empty( $USER_DATA ) or empty( $USER_DATA->count() ) ) {

			return false;

		}

		return $USER_DATA;

	}


	/**
	 * 	Gets many user_data records by status
	 * 
	 *	@param 	string 	$status
	 * 
	 * 	@return object
	 */
	public function fetchByStatus( string $status ) {

		if( empty( $status ) ) {

			return false;

		}

		$USER_DATA 	= self::where( 'status', $status )
			->orderBy( 'created_at', 'desc' )
			->get();

		if( empty( $USER_DATA ) or empty( $USER_DATA->count() ) ) {

			return false;

		}

		return $USER_DATA;

	}


	/**
	 * 	Inserts a user_data record
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

		$USER_DATA 	= new self( $INSERTION_DATA );

		$USER_DATA->save();

		return $USER_DATA;

	}


	/**
	 * 	Updates a user_data record
	 * 
	 *	@param 	array 	$DATA
	 * 
	 * 	@return object
	 */
	public function edit( array $DATA ) {

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
		
		return self::where( 'uid', user::getId() )
            ->update( $INSERTION_DATA );

	}

}