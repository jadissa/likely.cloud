<?php

namespace App\controlers\users;

use App\controlers\controler;

use Respect\Validation\Validator as v;

use App\models\session;

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

		$ROUTE = $REQUEST->getAttribute( 'route' );

		$service_type	= !empty( $ROUTE->getArgument( 'stype' ) ) ? $ROUTE->getArgument( 'stype' ) : null;

		$USER_EXPORTS 	= exports::getForUser( $service_type );

		$this->view->getEnvironment()->addGlobal( 'USER_EXPORTS', $USER_EXPORTS );

		return $this->view->render( $RESPONSE, 'users/exports.twig' );

	}


	/**
	 * 	Renders the export service_type view
	 *
	 *	@param 	object 	$REQUEST
	 * 	@param 	object 	$RESPONSE
	 *
	 * 	@return object
	 */
	public function getServiceType( $REQUEST, $RESPONSE ) {

		return self::get( $REQUEST, $RESPONSE );

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
			'service_stype'		=> v::arrayVal()->each( v::noWhitespace()->notEmpty() ),
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

		$service_type 	= array_values( $PARSED_REQUEST['service_stype'] )[0];

		if( !empty( $service_type and $service_type != 'all' ) ) {

			$url = $this->router->pathFor( 'users.exports.stype', [ 'stype' => $service_type ] );

			return $RESPONSE->withStatus(302)->withHeader( 'Location', $url );

		} else {

			return $RESPONSE->withRedirect( $this->router->pathFor( 'users.exports' ) );

		}

	}

}