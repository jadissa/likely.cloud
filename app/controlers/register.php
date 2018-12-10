<?php

namespace App\controlers;

use App\controlers\controler;

use Respect\Validation\Validator as v;

use App\models\service;

use App\controlers\lists\feed;

class register extends controler {

	/**
	 * 	Gets the register view
	 *
	 *	@param 	object 	$REQUEST
	 * 	@param 	object 	$RESPONSE
	 *
	 * 	@return object
	 */
	public function get( $REQUEST, $RESPONSE ) {

		//
		//	Fetch active service
		//
		$ACTIVE_SERVICES	= service::fetchActive();
		
		
		//
		//	Setup view
		//
		# @todo: make feed read from cache
		$FEED_DATA	= feed::getRecentRegistries();

		$this->view->getEnvironment()->addGlobal( 'SERVICE_REGISTRIES', $FEED_DATA['SERVICE_REGISTRIES'] );

		$this->view->getEnvironment()->addGlobal( 'SERVICES', $ACTIVE_SERVICES );

		if( empty( $ACTIVE_SERVICES ) ) {

			$this->flash->addMessage( 'error', 'Try again later' );

			$this->view->getEnvironment()->addGlobal( 'theme_disabled', true );

		}

		return $this->view->render( $RESPONSE, 'register.twig' );

	}


	/**
	 * 	Submits the register view
	 *
	 *	@param 	object 	$REQUEST
	 * 	@param 	object 	$RESPONSE
	 *
	 * 	@return bool
	 */
	public function post( $REQUEST, $RESPONSE ) {

		//
		//	Switch requested service
		//
		if( empty( $REQUEST->getParam( 'service' ) ) ) {

			return $RESPONSE->withRedirect( $this->router->pathFor( 'register' ) );

		}

		switch( $REQUEST->getParam( 'service' ) ) {

			case 'tumblr':

				$SERVICE 	= new services\tumblr( $this->CONTAINER );

				return $SERVICE->register( $REQUEST, $RESPONSE );

			break;

			case 'username':

				$SERVICE 	= new services\email( $this->CONTAINER );

				return $SERVICE->register( $REQUEST, $RESPONSE );

			break;

			default:

				return $RESPONSE->withRedirect( $this->router->pathFor( 'register' ) );

			break;

		}

	}

}