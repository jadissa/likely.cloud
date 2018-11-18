<?php

namespace App\controlers;

use App\controlers\listed\feed as feed;

class home extends controler {

	/**
	 * 	Renders the home view
	 *
	 *	@param 	object 	$REQUEST
	 * 	@param 	object 	$RESPONSE
	 *	@param 	object 	$ARGS
	 *
	 * 	@return bool
	 */
	public function index( $REQUEST, $RESPONSE, $ARGS ) {

		//
		//	Redirect check
		//
		if( empty( $_SESSION ) || empty( $_SESSION['user'] ) ) {

			return $RESPONSE->withRedirect( $this->router->pathFor( 'auth.signup' ) );
			
		}

		$FEED 		= new feed( $this->CONTAINER );

		$FEED_DATA 	= $FEED->getFeed( $REQUEST, $RESPONSE );

		$this->view->getEnvironment()->addGlobal( 'SERVICE_REGISTRIES', $FEED_DATA['SERVICE_REGISTRIES'] );

		
		//
		//	Setup view
		//
		return $this->view->render( $RESPONSE, 'home.twig' );

	}


	/**
	 * 	Renders the policy view
	 *
	 *	@param 	object 	$REQUEST
	 * 	@param 	object 	$RESPONSE
	 *	@param 	object 	$ARGS
	 *
	 * 	@return bool
	 */
	public function getPolicy( $REQUEST, $RESPONSE, $ARGS ) {

		return $this->view->render( $RESPONSE, 'policy.twig' );

	}

}