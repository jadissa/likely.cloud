<?php

namespace App\Validation;

use Respect\Validation\Validator as Respect;

use Respect\Validation\Exceptions\NestedValidationException;

# https://github.com/Respect/Validation

class validator {

	protected $ERRORS;

	public function validate( $REQUEST, array $RULES ) {

		foreach( $RULES as $field => $rule ) {

			try {

				$rule->setName( $field )->assert( $REQUEST->getParam( $field ) );

			} catch ( NestedValidationException $e ) {

				$this->ERRORS[ $field ]	= $e->getMessages();

			}

		}

		$_SESSION['ERRORS']	= $this->ERRORS;

		return $this;

	}


	public function failed() {

		return !empty( $this->ERRORS );

	}

}