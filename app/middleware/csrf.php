<?php

namespace App\middleware;

class csrf extends middleware {

	public function __invoke( $REQUEST, $RESPONSE, $NEXT ) {

		$this->view->getEnvironment()->addGlobal( 'csrf', [
			'field' 	=> '
				<input type="hidden" name="' . $this->csrf->getTokenNameKey() . '" value"' . $this->csrf->getTokenName() . '">

				<input type="hidden" name="' . $this->csrf->getTokenValueKey() . '" value"' . $this->csrf->getTokenValue() . '">
			',
		] );

		return $NEXT( $REQUEST, $RESPONSE );

	}

}