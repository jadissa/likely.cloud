<?php

namespace App\middleware;

class authenticatedRoutes extends middleware {

	public function __invoke( $REQUEST, $RESPONSE, $NEXT ) {

		if( empty( \App\models\user::authenticated( $this->CONTAINER, $this->SETTINGS ) ) ) {

			return $RESPONSE->withRedirect( $this->router->pathFor( 'login' ) );

		}

		return $NEXT( $REQUEST, $RESPONSE );

	}

}