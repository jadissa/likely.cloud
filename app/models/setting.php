<?php

//
//	https://laravel.com/docs/5.7
//

namespace App\models;

use \Illuminate\Database\Eloquent\Model;

class setting extends Model  {

	protected $tablename = 'settings';
	

	//
	//	Fields identified as updatable
	//
	protected $fillable	= [
		'name',
		'value',
	];


	/**
	 * 	Gets a setting by id
	 * 
	 *	@param 	int 	$id
	 * 
	 * 	@return object
	 */
	public function fetchById( $id ) {

		if( empty( $id ) ) {

			return false;

		}

		return self::where( 'id', $id )
			->orderBy( 'created_at', 'desc' )
			->first();

	}


	/**
	 * 	Gets a setting by name
	 * 
	 *	@param 	int 	$name
	 * 
	 * 	@return object
	 */
	public function fetchByName( $name ) {

		if( empty( $name ) ) {

			return false;

		}

		$SETTING 	= self::where( 'name', $name )
			->orderBy( 'created_at', 'desc' )
			->first();

		if( empty( $SETTING->count() ) ) {

			return false;

		}

		return $SETTING;

	}

}