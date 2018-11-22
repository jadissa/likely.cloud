<?php

namespace App\middleware;

class authenticatedRoutes extends middleware {

	public function __invoke( $REQUEST, $RESPONSE, $NEXT ) {

		if( empty( \App\models\user::authenticated() ) ) {

			$this->flash->addMessage( 'error', 'Please sign in or <a href="' . $this->router->pathFor( 'register' ) . '">register</a>' );

			return $RESPONSE->withRedirect( $this->router->pathFor( 'login' ) );

		}

		return $NEXT( $REQUEST, $RESPONSE );

	}

}