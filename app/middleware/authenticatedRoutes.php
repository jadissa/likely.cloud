<?php

namespace App\middleware;

class authenticatedRoutes extends middleware {

	public function __invoke( $REQUEST, $RESPONSE, $NEXT ) {

		if( !empty( $this->settings['debug'] ) && $this->settings['visitor'] != $_SERVER['REMOTE_ADDR'] ) {

			return $RESPONSE->withRedirect( $this->router->pathFor( 'disabled' ) );

		}


		if( empty( \App\models\user::authenticated( [ 'settings' => $this->settings, 'logger' => $this->logger ] ) ) ) {

			return $RESPONSE->withRedirect( $this->router->pathFor( 'login' ) );

		}

		return $NEXT( $REQUEST, $RESPONSE );

	}

}