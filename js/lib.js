var settings = ( function init()
{
	this.settings = {

		"server": 			{

			"dev": 				true

		},
		"form": 			{

			"dataType": 		"json",

			"timeout": 			500,

			"async": 			true,

			"type": 			"post",

			"discordLoginURL": 	"http://likely.cloud:50451/node_modules/discord-token-generator/api/discord/login",

			"emailURL": 		"http://likely.cloud:50451/api/email/login"

		}

	};

	return this.settings;

} )();


//
// Merge two objects
//
function mergeObjects( _obj1, _obj2 )
{

	var result = {};

	for( var key in _obj1 ) result[ key ] = _obj1[ key ];

	for( var key in _obj2 ) result[ key ] = _obj2[ key ];

	return result;

}


//
// May use as request hook
//
function request( _request )
{

	return _request;

}