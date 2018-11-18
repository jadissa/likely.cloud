<?php

namespace App\middleware;

class input extends middleware {

	public function __invoke( $REQUEST, $RESPONSE, $NEXT ) {

		$this->view->getEnvironment()->addGlobal( 'old_input', $_SESSION['old_input'] );

		$_SESSION['old_input']	= $REQUEST->getParams();

		return $NEXT( $REQUEST, $RESPONSE );

	}

}