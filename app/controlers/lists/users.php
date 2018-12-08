<?php

namespace App\controlers\lists;

use App\controlers\controler;

use App\models\user;

class users extends controler {

	/**
	 * 	Gets a list of users within context
	 * 	Declares psuedo user_data_status for afk
	 * 	Removes offline users
	 * 	afk and offline are defined app settings
	 *
	 *	@param 	string 	$context (online, invisible)
	 *
	 * 	@return array
	 */
	public function getUsers( $context = 'online' ) {

		$time_now 	= time();

		$USERS 		= user::fetchUsers( $context );

		foreach( $USERS as $id => $USER ) {

			$updated_user 	= strtotime( $USER->updated_at->toDateTimeString() );


			//
			//	Check for afk
			//
			if( abs( $time_now - $updated_user ) > self::$SETTINGS['afk_timeout'] ) {

				$USER->user_data_status 	= 'away';

			}


			//	Check for offline 
			if( abs( $time_now - $updated_user ) > self::$SETTINGS['offline_timeout'] ) {

				#unset( $USERS[ $id ] );

			}

		}

		return $USERS;

	}
	
}