<?php

namespace App\controlers;

use App\models\user;

use App\controlers\lists\feed as feed;

use App\controlers\lists\users as users;

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
	public function index( $REQUEST, $RESPONSE ) {

		//
		//	Setup view
		//
		$USERS 	= users::getUsers( 'public' );

		$this->view->getEnvironment()->addGlobal( 'BUDDIES', $USERS );


		//
		//	Redirect check
		//
		if( empty( user::authenticated() ) ) {

			return $RESPONSE->withRedirect( $this->router->pathFor( 'login' ) );
			
		}

		$FEED_DATA 	= feed::getRecentRegistries();

		$this->view->getEnvironment()->addGlobal( 'SERVICE_REGISTRIES', $FEED_DATA['SERVICE_REGISTRIES'] );


		$USERS 	= users::getUsers( 'registered' );

		$this->view->getEnvironment()->addGlobal( 'BUDDIES', $USERS );

		$this->view->getEnvironment()->addGlobal( 'user', $_SESSION['user'] );

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

		//
		//	Setup view
		//
		$this->view->getEnvironment()->addGlobal( 'user', !empty( $_SESSION['user'] ) ? $_SESSION['user'] : null );

		return $this->view->render( $RESPONSE, 'policy.twig' );

	}

}