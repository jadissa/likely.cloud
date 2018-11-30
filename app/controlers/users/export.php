<?php

namespace App\controlers\users;

use App\controlers\controler;

use Respect\Validation\Validator as v;

use App\models\users\exports;

class export extends controler {

	/**
	 * 	Renders the export view
	 *
	 *	@param 	object 	$REQUEST
	 * 	@param 	object 	$RESPONSE
	 *
	 * 	@return object
	 */
	public function get( $REQUEST, $RESPONSE ) {

		$USER_EXPORTS 	= exports::getForUser();

		$this->view->getEnvironment()->addGlobal( 'USER_EXPORTS', $USER_EXPORTS );

		
		//
		//	Setup view
		//
		$this->view->getEnvironment()->addGlobal( 'user', $_SESSION['user'] );

		return $this->view->render( $RESPONSE, 'users/exports.twig' );

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
		//	Validate request
		//
		$VALIDATION		= $this->validator->validate( $REQUEST, [
			'service_status'	=> v::arrayVal()->each( v::noWhitespace()->notEmpty() ),
		] );

		if( $VALIDATION->failed() ) {

			return $RESPONSE->withRedirect( $this->router->pathFor( 'users.exports' ) );

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
		$SAVED	= exports::save( $PARSED_REQUEST );

		if( !empty( $SAVED ) ) {

			$this->flash->addMessage( 'info', 'Successfully updated' );

		} else {

			$this->flash->addMessage( 'error', 'Failed to update' );

		}

		return $RESPONSE->withRedirect( $this->router->pathFor( 'users.exports' ) );

	}

}