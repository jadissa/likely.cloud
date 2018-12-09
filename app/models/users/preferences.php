<?php

namespace App\models\users;

use App\controlers\controler;

use App\models\user;

use App\models\user_data;

use App\models\user_service;

use App\models\service;

class preferences {

	/**
	 *	Gets a user's preference data
	 *
	 *	@return array
	 */
	public function getForUser() {

		$USER_PREFERNCES	= [];

		var_dump( $_SESSION );

		$USER_DATA			= user_data::fetchById( user::getId() );

		$USER_PREFERNCES = [
			'created_at'	=> $USER_DATA->created_at,
			'updated_at'	=> $USER_DATA->updated_at,
			'status'		=> $USER_DATA->status
		];

		return !empty( $USER_PREFERNCES ) ? $USER_PREFERNCES : [];

	}


	/**
	 *	Saves a user's preference data
	 *
	 *	@return array
	 */
	public function save( $PREFERENCES ) {

		if( !empty( $PREFERENCES['user_status'] ) ) {

			return user_data::edit( ['status' => $PREFERENCES['user_status'] ] );

		}

		return false;

	}

}