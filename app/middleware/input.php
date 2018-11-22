<?php

namespace App\middleware;

class input extends middleware {

	public function __invoke( $REQUEST, $RESPONSE, $NEXT ) {

		if( !empty( $_SESSION['old_input'] ) ) {

			$this->view->getEnvironment()->addGlobal( 'old_input', $_SESSION['old_input'] );

		}


		//
		//	Parse request
		//
		$PARSED_REQUEST 	= $REQUEST->getParams();


		//
		//	Do not cache certain inputs
		//
		foreach( $PARSED_REQUEST as $name => $vale ) {

			//
			//	password
			//
			if( strpos( $name, 'pwd' ) !== false ) {

				if( isset( $_SESSION['old_input'][ $name ] ) )	unset( $_SESSION['old_input'][ $name ] );

				continue;

			}

			$_SESSION['old_input'][ $name ]	= $vale;

		}

		return $NEXT( $REQUEST, $RESPONSE );

	}

}