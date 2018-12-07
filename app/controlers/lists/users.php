<?php

namespace App\controlers\lists;

use App\controlers\controler;

use App\models\user;

class users {

	//
	//	1 hour
	//
	protected static $TIME_LAPSED	= 60 * 1;


	/**
	 * 	Gets a list of users within context
	 * 	Declare psuedo status after time lapsed
	 *
	 *	@param 	string 	$context (online, invisible)
	 *
	 * 	@return array
	 */
	public function getUsers( $context = 'online' ) {

		$time_now 	= time();

		$USERS 		= user::fetchUsers( $context );

		foreach( $USERS as $USER ) {

			/*
			if( user::getId() == 1 and $USER->id == 1 ) {

				$updated_user 	= strtotime( $USER->updated_at->toDateTimeString() );

				var_dump( date( 'Y-m-d H:i:s:v', $updated_user ) );

				if( abs( $updated_user  - $time_now ) / 60 > self::$TIME_LAPSED ) {

					var_dump( abs( $updated_user - $time_now ) / 60 );

					var_dump( self::$TIME_LAPSED );

					exit( 'yup');

				} else {

					var_dump( abs( $updated_use - $time_now ) / 60 );

					var_dump( self::$TIME_LAPSED );

					exit( 'nope');

				}

			}
			*/


		}

		return $USERS;

	}
	
}