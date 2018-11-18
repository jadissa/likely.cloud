<?php

$APP->get( '/', 'home:index' )->setName( 'home' );

$APP->get( '/policy', 'home:getPolicy' )->setName( 'policy' );

$APP->get( '/services', 'services:getActive' );

$APP->get( '/services/tumblr', 'services:getTumblr' )->setName( 'service.tumblr' );

$APP->get( '/services/tumblr_auth', 'services:authTumblr' )->setName( 'service.tumblr_auth');

$APP->get( '/auth/signup', 'auth:getSignup' )->setName( 'auth.signup' );

$APP->post( '/auth/signup', 'auth:postSignup' );

$APP->get( '/auth/preferences', 'auth:getPreferences' )->setName( 'auth.preferences' );

$APP->post( '/auth/preferences', 'auth:postPreferences' );

$APP->get( '/auth/assets', 'auth:getAssets' )->setName( 'auth.assets' );

$APP->post( '/auth/assets', 'auth:postAssets' );

$APP->get( '/feed', 'feed:getFeed' );