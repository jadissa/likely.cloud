<?php

namespace App\controlers;

use Interop\Container\ContainerInterface;

use App\controlers\lists\users as users;

abstract class controler {

	protected $CONTAINER;


	/**
	 * 	Instantiates a controler
	 *
	 *	@param 	object 	$CONTAINER
	 *
	 * 	@return void
	 */
	public function __construct( ContainerInterface $CONTAINER ) {

		$this->CONTAINER 	= $CONTAINER;

		$this->view->getEnvironment()->addGlobal( 'theme', $this->settings['theme'][0] );


		//
		//	Routes without additionals
		//
		$BARE_ROUTES 	= [ 'policy' ];

		foreach( $BARE_ROUTES as $bare_route ) {

			if( stripos( $this->request->getUri()->getPath(), $bare_route ) !== false ) {
				
				return;

			}

		}

		//
		//	Get online users
		//
		$USERS 	= users::getUsers( $this->settings, 'online' );

		$this->view->getEnvironment()->addGlobal( 'BUDDIES', $USERS );
		
	}


	/**
	 * 	Magic method to get a property for the instantiated instance
	 *
	 *	@param 	string 	$property
	 *
	 * 	@return mixed
	 */
	public function __get( $property ) {

		if( empty( $this->CONTAINER[ $property ] ) ) {

			return false;

		}

		return $this->CONTAINER[ $property ];

	}

}