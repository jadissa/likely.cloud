<?php

namespace App\controlers\users;

use App\controlers\controler;

use Respect\Validation\Validator as v;

use App\models\session;

use App\models\users\preferences;

class preference extends controler {

	/**
	 * 	Renders the preference view
	 *
	 *	@param 	object 	$REQUEST
	 * 	@param 	object 	$RESPONSE
	 *
	 * 	@return object
	 */
	public function get( $REQUEST, $RESPONSE ) {

		$USER_PREFERENCES 	= preferences::getForUser();

		$this->view->getEnvironment()->addGlobal( 'USER_PREFERENCES', $USER_PREFERENCES );

		
		//
		//	Setup view
		//
		$this->view->getEnvironment()->addGlobal( 'user', session::get( 'user' ) );

		return $this->view->render( $RESPONSE, 'users/preferences.twig' );

	}


	/**
	 * 	Submits the preference view
	 *
	 *	@param 	object 	$REQUEST
	 * 	@param 	object 	$RESPONSE
	 *
	 * 	@return object
	 */
	public function post( $REQUEST, $RESPONSE ) {

		//
		//	Validate request
		//
		$VALIDATION		= $this->validator->validate( $REQUEST, [
			'user_status'		=> v::noWhitespace()->notEmpty(),
		] );

		if( $VALIDATION->failed() ) {

			return $RESPONSE->withRedirect( $this->router->pathFor( 'users.preferences' ) );

		}


		//
		//	Parse request
		//
		$PARSED_REQUEST 	= $REQUEST->getParsedBody();

		if( !empty( $this->settings['debug'] ) and !empty( $PARSED_REQUEST ) ) {

			$this->logger->addInfo( serialize( $PARSED_REQUEST ) );

		}


		//
		//	Save preference
		//
		$SAVED	= preferences::save( $PARSED_REQUEST );

		if( !empty( $SAVED ) ) {

			$this->flash->addMessage( 'info', 'Successfully updated' );

		} else {

			$this->flash->addMessage( 'error', 'Failed to update' );

		}

		return $RESPONSE->withRedirect( $this->router->pathFor( 'users.preferences' ) );

	}

}