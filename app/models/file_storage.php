<?php

namespace App\models;

class file_storage {

	protected static $OPTIONS;

	public static function initialize( $OPTIONS = [] ) {

		$DEFAULT_COOKIE_PARAMS 	= session_get_cookie_params();

		self::$OPTIONS 	= [
		    'save_path'         => !empty( $OPTIONS['save_path'] ) ? $OPTIONS['save_path'] : session_save_path(),
		    'name'              => !empty( $OPTIONS['name'] ) ? $OPTIONS['name'] : session_name(),
		    'save_handler'      => !empty( $OPTIONS['save_handler'] ) ? $OPTIONS['save_handler'] : 'files',
		    'gc_probability'    => !empty( $OPTIONS['gc_probability'] ) ? $OPTIONS['gc_probability'] : '1',
		    'gc_divisor'        => !empty( $OPTIONS['gc_divisor'] ) ? $OPTIONS['gc_divisor'] : '100',
		    'gc_maxlifetime'    => !empty( $OPTIONS['gc_maxlifetime'] ) ? $OPTIONS['gc_maxlifetime'] : '1440',
		    'cookie_lifetime'   => !empty( $OPTIONS['cookie_lifetime'] ) ?$OPTIONS['cookie_lifetime'] : $DEFAULT_COOKIE_PARAMS['lifetime'],
		    'cookie_path'       => !empty( $OPTIONS['cookie_path'] ) ? $OPTIONS['cookie_path'] : $DEFAULT_COOKIE_PARAMS['path'],
		    'cookie_domain'     => !empty( $OPTIONS['cookie_domain'] ) ? $OPTIONS['cookie_domain'] : $DEFAULT_COOKIE_PARAMS['domain'],
		    'cookie_secure'     => !empty( $OPTIONS['cookie_secure'] ) ? $OPTIONS['cookie_secure'] : $DEFAULT_COOKIE_PARAMS['secure'],
		    'cookie_httponly'   => !empty( $OPTIONS['cookie_httponly'] ) ? $OPTIONS['cookie_httponly'] : $DEFAULT_COOKIE_PARAMS['httponly'],
		    'use_strict_mode'   => !empty( $OPTIONS['use_strict_mode'] ) ? $OPTIONS['use_strict_mode'] : '0',
		    'use_cookies'       => !empty( $OPTIONS['use_cookies'] ) ? $OPTIONS['use_cookies'] : '1',
		    'use_only_cookies'  => !empty( $OPTIONS['use_only_cookies'] ) ? $OPTIONS['use_only_cookies'] : '1',
		    'cache_limiter'     => !empty( $OPTIONS['cache_limiter'] ) ? $OPTIONS['cache_limiter'] : session_cache_limiter(),
		    'cache_expire'      => !empty( $OPTIONS['cache_expire'] ) ? $OPTIONS['cache_expire'] : session_cache_expire(),
		    'referer_check'     => !empty( $OPTIONS['referer_check'] ) ? $OPTIONS['referer_check'] : '',
		];

		return self::$OPTIONS;

	}

}