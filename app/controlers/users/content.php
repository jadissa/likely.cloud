<?php

namespace App\controlers\users;

use App\controlers\controler;

use Respect\Validation\Validator as v;

use App\models\session;

use App\models\users\exports;

class content extends controler {

	/**
	 * 	Renders the content view
	 *
	 *	@param 	object 	$REQUEST
	 * 	@param 	object 	$RESPONSE
	 *
	 * 	@return object
	 */
	public function get( $REQUEST, $RESPONSE ) {

		$ROUTE = $REQUEST->getAttribute( 'route' );

		$service_type	= !empty( $ROUTE->getArgument( 'stype' ) ) ? $ROUTE->getArgument( 'stype' ) : null;

		$this->view->getEnvironment()->addGlobal( 'user', session::get( 'user' ) );

		return $this->view->render( $RESPONSE, 'users/content.twig' );

	}


	/**
	 * 	Submits the content view
	 *
	 *	@param 	object 	$REQUEST
	 * 	@param 	object 	$RESPONSE
	 *
	 * 	@return object
	 */
	public function post( $REQUEST, $RESPONSE ) {

		

	}

}