<?php

#$APP->get( '/services', 'services:getActive' );

$APP->get( '/tests', 'home:test' );

use App\middleware\authenticatedRoutes;

use App\middleware\guestRoutes;


//
//	Authenticated routes
//
$APP->group( '', function() {

	$this->get( '/', 'home:index' )->setName( 'home' );

	/*
	$this->get( '/auth/preferences', 'auth:getPreferences' )->setName( 'auth.preferences' );

	$this->post( '/auth/preferences', 'auth:postPreferences' );

	$this->get( '/auth/assets', 'auth:getAssets' )->setName( 'auth.assets' );

	$this->post( '/auth/assets', 'auth:postAssets' );
	*/

	$this->get( '/logout', 'logout:get' )->setName( 'logout' );

})->add( new authenticatedRoutes( $CONTAINER ) );


//
// Guest routes
//
$APP->group( '', function() {

	$this->get( '/register', 'register:get' )->setName( 'register' );

	$this->post( '/register', 'register:post' );


	$this->get( '/login', 'login:get' )->setName( 'login' );

	$this->post( '/login', 'login:post' );


	/*
	$this->get( '/login/email', 'email:login' )->setName( 'services.email.login' );

	$this->get( '/login/tumblr', 'tumblr:login' )->setName( 'services.tumblr.login' );


	$this->post( '/email', 'email:register' )->setName( 'services.email.register' );

	$this->post( '/tumblr', 'tumblr:register' )->setName( 'services.tumblr.register' );
	*/


	/*
	$this->get( '/services/tumblr_auth', 'services:authTumblr' )->setName( 'service.tumblr_auth');

	$this->get( '/services/tumblr_persist', 'services:persistTumblr' )->setName( 'service.tumblr_persist');

	$this->get( '/services/email_persist', 'services:persistEmail' )->setName( 'service.email_persist' );
	*/

})->add( new guestRoutes( $CONTAINER ) );


//
//	Everyone routes
//
$APP->get( '/policy', 'home:getPolicy' )->setName( 'policy' );