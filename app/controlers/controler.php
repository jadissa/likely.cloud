<?php

namespace App\controlers;

use Interop\Container\ContainerInterface;

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

		$this->CONTAINER = $CONTAINER;

		$this->view->getEnvironment()->addGlobal( 'theme', $this->settings['theme'][0] );

		ini_set( 'date.timezone', $this->settings['timezone'] );
		
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