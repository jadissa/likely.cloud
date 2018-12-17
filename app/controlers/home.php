<?php

namespace App\controlers;

use App\models\user;

use App\models\session;

use App\controlers\controler;

use App\controlers\lists\feed as feed;

class home extends controler {

	/**
	 * 	Renders the home view
	 *
	 *	@param 	object 	$REQUEST
	 * 	@param 	object 	$RESPONSE
	 *
	 * 	@return bool
	 */
	public function index( $REQUEST, $RESPONSE ) {

		//
		//	Setup view
		//
		$FEED_DATA 	= feed::getRecentRegistries();

		$this->view->getEnvironment()->addGlobal( 'user', session::get( 'user' ) );

		$this->view->getEnvironment()->addGlobal( 'SERVICE_REGISTRIES', $FEED_DATA['SERVICE_REGISTRIES'] );

		return $this->view->render( $RESPONSE, 'home.twig' );

	}


	/**
	 * 	Renders the policy view
	 *
	 *	@param 	object 	$REQUEST
	 * 	@param 	object 	$RESPONSE
	 *
	 * 	@return bool
	 */
	public function getPolicy( $REQUEST, $RESPONSE ) {

		$this->view->getEnvironment()->addGlobal( 'user', session::get( 'user' ) );

		return $this->view->render( $RESPONSE, 'policy.twig' );

	}


	/**
	 * 	Renders the disabled view
	 *
	 *	@param 	object 	$REQUEST
	 * 	@param 	object 	$RESPONSE
	 *
	 * 	@return bool
	 */
	public function getDisabled( $REQUEST, $RESPONSE ) {

		return $this->view->render( $RESPONSE, 'disabled.twig' );

	}

}