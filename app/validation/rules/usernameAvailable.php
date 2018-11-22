<?php

namespace App\validation\rules;

use App\models\user;


use Respect\Validation\Validatable;

class usernameAvailable extends AbstractRule {

	public function validate( $username ) {

		return empty( user::fetchByUsername( $username ) );

	}

}