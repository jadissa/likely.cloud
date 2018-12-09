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

	
	$this->get( '/preferences', 'preference:get' )->setName( 'users.preferences' );

	$this->post( '/preferences', 'preference:post' );


	$this->get( '/services', 'service:get' )->setName( 'users.services' );

	$this->post( '/services', 'service:post' );


	$this->get( '/exports', 'export:get' )->setName( 'users.exports' );

	$this->get( '/exports/{stype}', 'export:getServiceType' )->setName( 'users.exports.stype' );

	$this->post( '/exports', 'export:post' );


	$this->get( '/content', 'content:get' )->setName( 'users.content' );

	/*
	$this->get( '/auth/assets', 'auth:getAssets' )->setName( 'auth.assets' );

	$this->post( '/auth/assets', 'auth:postAssets' );
	*/

	$this->get( '/logout', 'logout:get' )->setName( 'logout' );

})->add( new authenticatedRoutes( $CONTAINER, $SETTINGS ) );


//
// Guest routes
//
$APP->group( '', function() {

	$this->get( '/register', 'register:get' )->setName( 'register' );

	$this->post( '/register', 'register:post' );


	$this->get( '/login', 'login:get' )->setName( 'login' );

	$this->post( '/login', 'login:post' );


	$this->get( '/tumblr/auth', 'tumblr:callback' )->setName( 'service.tumblr.callback' );


	$this->get( 'imgur/auth', 'imgur:callback' )->setName( 'service.imgur.callback' );


	/*
	$this->get( '/services/tumblr_auth', 'services:authTumblr' )->setName( 'service.tumblr_auth');

	$this->get( '/services/tumblr_persist', 'services:persistTumblr' )->setName( 'service.tumblr_persist');

	$this->get( '/services/email_persist', 'services:persistEmail' )->setName( 'service.email_persist' );
	*/

})->add( new guestRoutes( $CONTAINER, $SETTINGS ) );


//
//	Everyone routes
//
$APP->get( '/policy', 'home:getPolicy' )->setName( 'policy' );