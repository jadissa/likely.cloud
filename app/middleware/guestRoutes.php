<?php

namespace App\middleware;

class guestRoutes extends middleware {

	public function __invoke( $REQUEST, $RESPONSE, $NEXT ) {

		if( !empty( $this->SETTINGS['debug'] ) && $this->SETTINGS['visitor'] != $_SERVER['REMOTE_ADDR'] ) {

			return $RESPONSE->withRedirect( $this->router->pathFor( 'disabled' ) );

		}

		if( !empty( \App\models\user::authenticated( $this->CONTAINER, $this->SETTINGS ) ) ) {

			return $RESPONSE->withRedirect( $this->router->pathFor( 'home' ) );

		}

		return $NEXT( $REQUEST, $RESPONSE );

	}

}