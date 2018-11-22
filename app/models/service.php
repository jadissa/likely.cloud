<?php

//
//	https://laravel.com/docs/5.7
//

namespace App\models;

use \Illuminate\Database\Eloquent\Model;

class service extends Model  {

	protected $tablename = 'services';
	

	//
	//	Fields identified as updatable
	//
	protected $fillable	= [
		'name',
		'status',
		'internal',
	];


	/**
	 * 	Gets a service by id
	 * 
	 *	@param 	int 	$id
	 * 
	 * 	@return object
	 */
	public function fetchById( $id ) {

		if( empty( $id ) ) {

			return false;

		}

		$SERVICE 	= self::where( 'id', $id )
			->orderBy( 'created_at', 'desc' )
			->first();

		if( empty( $SERVICE ) or empty( $SERVICE->count() ) ) {

			return false;

		}

		return $SERVICE;

	}


	/**
	 * 	Gets a service by name
	 * 
	 *	@param 	int 	$name
	 * 
	 * 	@return object
	 */
	public function fetchByName( $name ) {

		if( empty( $name ) ) {

			return false;

		}

		$SERVICE 	= self::where( 'name', $name )
			->orderBy( 'created_at', 'desc' )
			->first();

		if( empty( $SERVICE ) or empty( $SERVICE->count() ) ) {

			return false;

		}

		return $SERVICE;

	}


	/**
	 * 	Gets active services
	 * 
	 * 	@return object
	 */
	public function fetchActive() {

		$ACTIVE_SERVICES	= self::where( 'status', 'active' )
			->orderBy( 'created_at', 'desc' )
			->get();

		if( empty( $ACTIVE_SERVICES ) or empty( $ACTIVE_SERVICES->count() ) ) {

			return false;

		}

		return $ACTIVE_SERVICES;

	}


	/**
	 * 	Inserts a service record
	 * 
	 *	@param 	string 	$name
	 *	@param 	string 	$status
	 *	@param 	string 	$inernal
	 * 
	 * 	@return object
	 */
	public function insert( $name, $status = 'active', $inernal = 0 ) {

		if( empty( $name ) ) {

			return false;

		}

		return self::create( [
			'name'		=> $name,
			'status'	=> $status,
			'internal'	=> $internal
		] );

	}


	/**
	 * 	Updates a service name
	 * 
	 *	@param 	int 	$id
	 *	@param 	string 	$name
	 * 
	 * 	@return object
	 */
	public function updateName( $id, $name ) {

		if( empty( $id ) or empty( $name ) ) {

			return false;

		}

		return self::where( 'id', $id )
            ->update( [ 'name' => $name ] );

	}


	/**
	 * 	Updates a service status
	 * 
	 *	@param 	int 	$id
	 *	@param 	string 	$status
	 * 
	 * 	@return object
	 */
	public function updateStatus( $id, $status ) {

		if( empty( $id ) or empty( $status ) ) {

			return false;

		}

		return self::where( 'id', $id )
            ->update( [ 'status' => $status ] );

	}

}