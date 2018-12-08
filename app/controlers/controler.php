<?php

namespace App\controlers;

use Interop\Container\ContainerInterface;

use App\controlers\lists\users as users;

abstract class controler {

	protected $CONTAINER;

	public static $SETTINGS;


	/**
	 * 	Instantiates a controler
	 *
	 *	@param 	object 	$CONTAINER
	 *
	 * 	@return void
	 */
	public function __construct( ContainerInterface $CONTAINER ) {

		$this->CONTAINER 	= $CONTAINER;

		self::$SETTINGS 	= $this->settings;

		$this->view->getEnvironment()->addGlobal( 'theme', self::$SETTINGS['theme'][0] );

		ini_set( 'date.timezone', $this->settings['timezone'] );


		$USERS 	= users::getUsers( 'online' );

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