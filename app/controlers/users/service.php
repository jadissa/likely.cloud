<?php

namespace App\controlers\users;

use App\controlers\controler;

use Respect\Validation\Validator as v;

use App\models\users\exports;

class service extends controler {

	/**
	 * 	Renders the export view
	 *
	 *	@param 	object 	$REQUEST
	 * 	@param 	object 	$RESPONSE
	 *
	 * 	@return object
	 */
	public function get( $REQUEST, $RESPONSE ) {

		$ROUTE = $REQUEST->getAttribute( 'route' );

		$service_type		= !empty( $ROUTE->getArgument( 'stype' ) ) ? $ROUTE->getArgument( 'stype' ) : null;

		$ACTIVE_SERVICES 	= exports::getActive();

		$USER_SERVICES 		= exports::getForUser();

		$AVAILABLE_SERVICES	= [];

		foreach( $ACTIVE_SERVICES as $ACTIVE_SERVICE ) {

			if( empty( $USER_SERVICES[ $ACTIVE_SERVICE->id ] ) ) {

				array_push( $AVAILABLE_SERVICES, $ACTIVE_SERVICE );

			}

		}

		$this->view->getEnvironment()->addGlobal( 'AVAILABLE_SERVICES', $AVAILABLE_SERVICES );

		
		//
		//	Setup view
		//
		$this->view->getEnvironment()->addGlobal( 'user', $_SESSION['user'] );

		return $this->view->render( $RESPONSE, 'users/services.twig' );

	}


	/**
	 * 	Submits the export view
	 *
	 *	@param 	object 	$REQUEST
	 * 	@param 	object 	$RESPONSE
	 *
	 * 	@return object
	 */
	public function post( $REQUEST, $RESPONSE ) {

		return $RESPONSE;

	}

}