<?php

namespace App\controlers;

use App\controlers\controler;

use App\models\user;

class logout extends controler {

	/**
	 * 	Gets the login view
	 *
	 *	@param 	object 	$REQUEST
	 * 	@param 	object 	$RESPONSE
	 *
	 * 	@return object
	 */
	public function get( $REQUEST, $RESPONSE ) {

		user::logout( [ 'settings' => $this->settings, 'logger' => $this->logger ] );

		return $RESPONSE->withRedirect( $this->router->pathFor( 'login' ) );

	}

}