<?php

namespace App\middleware;

class guestRoutes extends middleware {

	public function __invoke( $REQUEST, $RESPONSE, $NEXT ) {

		if( isset( $_SESSION['user']['uid'] ) ) {

			return $RESPONSE->withRedirect( $this->router->pathFor( 'home' ) );

		}

		return $NEXT( $REQUEST, $RESPONSE );

	}

}