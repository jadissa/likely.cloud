<?php

namespace App\controlers\users;

use App\controlers\controler;

use Respect\Validation\Validator as v;

use App\models\session;

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

			if( !empty( $USER_SERVICES[ $ACTIVE_SERVICE->id ] ) ) {

				$ACTIVE_SERVICE->setAttribute( 'user_enabled', true );

			}

			array_push( $AVAILABLE_SERVICES, $ACTIVE_SERVICE );

		}

		$this->view->getEnvironment()->addGlobal( 'user', session::get( 'user' ) );

		$this->view->getEnvironment()->addGlobal( 'AVAILABLE_SERVICES', $AVAILABLE_SERVICES );

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

		//
		//	Parse request
		//
		$PARSED_REQUEST 	= $REQUEST->getParsedBody();

		if( !empty( $this->settings['debug'] ) and !empty( $PARSED_REQUEST ) ) {

			$this->logger->addInfo( serialize( $PARSED_REQUEST ) );

		}

		return $RESPONSE;

	}

}