<?php

namespace App\middleware;

class flash extends middleware {

	public function __invoke( $REQUEST, $RESPONSE, $NEXT ) {

		$this->view->getEnvironment()->addGlobal( 'flash', $this->flash );

		return $NEXT( $REQUEST, $RESPONSE );

	}

}