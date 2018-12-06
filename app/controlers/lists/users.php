<?php

namespace App\controlers\lists;

use App\models\user;

class users {

	/**
	 * 	Gets a list of users within context
	 *
	 *	@param 	string 	$context (registered, public)
	 *
	 * 	@return array
	 */
	public function getUsers( $context = 'registered' ) {

		return user::fetchUsers( $context );

	}
	
}