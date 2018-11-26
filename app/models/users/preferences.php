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

		$USER_PREFERNCES	= [
			'ACCOUNT'		=> null,
			'SERVICES'		=> [],
		];

		$USER_DATA			= user_data::fetchById( user::getId() );

		$USER_PREFERNCES['ACCOUNT'] = [
			'created_at'	=> $USER_DATA->created_at,
			'updated_at'	=> $USER_DATA->updated_at,
			'status'		=> $USER_DATA->status
		];

		$ACTIVE_SERVICES	= service::fetchActive();

		foreach( $ACTIVE_SERVICES as $ACTIVE_SERVICE ) {

			$USER_SERVICE 	= user_service::fetchById( $ACTIVE_SERVICE->id );

			$SERVICE 		= service::fetchById( $USER_SERVICE->id );

			if( empty( $USER_SERVICE ) )	continue;

			array_push( $USER_PREFERNCES['SERVICES'], [
				'id'			=> $USER_SERVICE->id,
				'name'			=> $SERVICE->name,
				'created_at'	=> $USER_SERVICE->created_at,
				'updated_at'	=> $USER_SERVICE->updated_at,
				'status'		=> $USER_SERVICE->status
			] );

		}

		return !empty( $USER_PREFERNCES ) ? $USER_PREFERNCES : [];

	}


	/**
	 *	Saves a user's preference data
	 *
	 *	@return array
	 */
	public function save( $PREFERENCES ) {

		if( !empty( $PREFERENCES['user_status'] ) ) {

			$USER_UPDATE 	= user_data::update( ['status' => $PREFERENCES['user_status'] ] );

		}
		
		if( !empty( $PREFERENCES['service_status'] ) ) {

			foreach( $PREFERENCES['service_status'] as $service_id => $status ) {

				$SERVICE_UPDATE	= user_service::update( $service_id, ['status' => $status ] );

			}

		}

		return true;

	}

}