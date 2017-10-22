<?php

require 'vendor/autoload.php';
use \Discord\OAuth\Discord;
$provider = new \Discord\OAuth\Discord([
	'clientId'     => '371313023328976896',
	'clientSecret' => 'HPHsYu7ckvqPQoJboLGaLvg4Z9gSOECu',
	'redirectUri'  => 'http://urlike.me',
]);

if (! isset($_GET['code'])) {
	echo '<a href="'.$provider->getAuthorizationUrl().'">Login with Discord</a>';
} else {
	$token = $provider->getAccessToken('authorization_code', [
		'code' => $_GET['code'],
	]);

	// Get the user object.
	$user = $provider->getResourceOwner($token);

	// Get the guilds and connections.
	$guilds = $user->guilds;
	$connections = $user->connections;

	// Accept an invite
	$invite = $user->acceptInvite('https://discord.gg/0SBTUU1wZTUo9F8v');

	// Get a refresh token
	$refresh = $provider->getAccessToken('refresh_token', [
		'refresh_token' => $getOldTokenFromMemory->getRefreshToken(),
	]);

	// Store the new token.
}