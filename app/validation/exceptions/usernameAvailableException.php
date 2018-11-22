<?php

namespace App\validation\exceptions;

use Respect\Validation\Exceptions\ValidationException;

class usernameAvailableException extends ValidationException {

	public static $defaultTemplates = [
		self::MODE_DEFAULT 	=> [
			self::STANDARD 	=> 'Username is not available',
		],
	];

}