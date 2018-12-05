<?php

namespace App\models\users;

use App\controlers\controler;

use App\models\user;

use App\models\user_data;

use App\models\user_service;

use App\models\service;

class exports {

	/**
	 *	Gets a user's export data
	 *
	 *	@return array
	 */
	public function getForUser( string $service_type = null ) {

		$USER_EXPORTS	= [];

		$ACTIVE_SERVICES	= user_service::fetchForUser( $service_type );

		if( empty( $ACTIVE_SERVICES ) ) {

			return $USER_EXPORTS;
		}

		foreach( $ACTIVE_SERVICES as $ACTIVE_SERVICE ) {

			$SERVICE 		= service::fetchById( $ACTIVE_SERVICE->sid );

			$USER_EXPORTS[ $ACTIVE_SERVICE->sid ] = [
				'id'			=> $ACTIVE_SERVICE->sid,
				'name'			=> $SERVICE->name,
				'created_at'	=> $ACTIVE_SERVICE->created_at,
				'updated_at'	=> $ACTIVE_SERVICE->updated_at,
				'status'		=> $ACTIVE_SERVICE->status,
				'stype'			=> $ACTIVE_SERVICE->stype,
			];

		}

		return !empty( $USER_EXPORTS ) ? $USER_EXPORTS : [];

	}


	/**
	 *	Gets a type's export data
	 *
	 *	@return array
	 */
	public function getForType() {

		$TYPE_EXPORTS	= [];

		$TYPE_SERVICES	= service::fetchForType();

		foreach( $TYPE_SERVICES as $TYPE_SERVICE ) {

			$SERVICE 	= service::fetchById( $TYPE_SERVICE->id );

			array_push( $TYPE_EXPORTS, [
				'id'			=> $TYPE_SERVICE->id,
				'name'			=> $SERVICE->name,
				'created_at'	=> $TYPE_SERVICE->created_at,
				'updated_at'	=> $TYPE_SERVICE->updated_at,
				'status'		=> $TYPE_SERVICE->status,
				'stype'			=> $TYPE_SERVICE->stype,
			] );

		}

		return !empty( $TYPE_EXPORTS ) ? $TYPE_EXPORTS : [];

	}


	/**
	 * 	Gets active services
	 * 
	 * 	@return object
	 */
	public function getActive() {

		return service::fetchActive();

	}


	/**
	 *	Saves a user's export data
	 *
	 *	@return array
	 */
	public function save( $EXPORTS ) {

		#print'<pre>';print_r( $EXPORTS );print'</pre>';exit;

		foreach( $EXPORTS['service_status'] as $service_id => $status ) {

			$SERVICE_UPDATE	= user_service::edit( $service_id, ['status' => $status ] );

		}

		foreach( $EXPORTS['service_stype'] as $service_id => $stype ) {

			$SERVICE_UPDATE	= user_service::edit( $service_id, ['stype' => $stype ] );

		}

		return true;

	}

}