<?php

namespace App\middleware;

class middleware {

	protected $CONTAINER;

	public function __construct( $CONTAINER, $SETTINGS ) {

		$this->CONTAINER	= $CONTAINER;

		$this->SETTINGS 	= $SETTINGS;

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