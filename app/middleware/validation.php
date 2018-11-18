<?php

namespace App\middleware;

class validation extends middleware {

	public function __invoke( $REQUEST, $RESPONSE, $NEXT ) {

		if( !empty( $_SESSION['ERRORS'] ) ) {

			$this->view->getEnvironment()->addGlobal( 'ERRORS', $_SESSION['ERRORS'] );

			unset( $_SESSION['ERRORS'] );
		}

		return $NEXT( $REQUEST, $RESPONSE );

	}

}