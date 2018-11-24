<?php

//
//	https://laravel.com/docs/5.7
//

namespace App\models;

use \Illuminate\Database\Eloquent\Model;

class transaction extends Model  {

	protected $tablename = 'transactions';
	

	//
	//	Fields identified as updatable
	//
	protected $fillable	= [
		'sid',
		'session_id',
		'oauth_token',
		'oauth_token_secret',
		'data',
	];


	/**
	 * 	Inserts a transaction record
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
	 * 	Gets a service transaction for the current user
	 * 	Results are limited to last 15 minutes
	 * 
	 *	@param 	int 	$service_id
	 * 
	 * 	@return object
	 */
	public function fetchByService( int $service_id ) {

		if( empty( $service_id ) ) {

			return false;

		}

		$TRANSACTION 	= self::select( 'oauth_token', 'oauth_token_secret', 'data' )
			->where( 'sid', $service_id )
			->where( 'session_id', session_id() )
			->where( 'created_at', '>=', date( 'Y-m-d H:i:s', time() - ( 60 * 15 ) ) )
			->orderBy( 'created_at', 'desc' )
			->first();

		if( empty( $TRANSACTION ) ) {

			return false;

		}

		return $TRANSACTION;

	}


	/**
	 * 	Gets service transactions older than a given date
	 * 
	 *	@param 	int 	$service_id
	 * 	@param 	string 	$from_date
	 * 
	 * 	@return object
	 */
	public function fetchByServiceDate( int $service_id, string $from_date ) {

		if( empty( $service_id ) or empty( $from_date ) ) {

			return false;

		}

		$TRANSACTIONS 	= self::select( '*' )
			->where( 'sid', $service_id )
			->where( 'created_at', '<=', $from_date )
			->orderBy( 'created_at', 'desc' )
			->get();

		if( empty( $TRANSACTIONS ) ) {

			return false;

		}

		return $TRANSACTIONS;

	}

}