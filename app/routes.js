var url = require( 'url' );

var fs = require( 'fs' );

var geoip = require('../tests/geoip-lite');

function renderHTML( path, resp ) {

	fs.readFile( path, null, function( error, data ) {

		if( error ) {

			resp.writeHead( 404 );

			resp.write( 'File not found!' );

		} else {

			resp.write( data );
		}

		resp.end();

	});

}

module.exports = {

	handleRequest: function( req, resp) {

		resp.writeHead( 200, {'Content-Type': 'text/html'} );

		var path = url.parse( req.url ).pathname;

		switch( path ) {

			case '/':

				renderHTML( './index.html', resp );

				break;

			case '/geoip':

				eval( fs.readFileSync('../tests/geoip-lite.js') +'' );

				console.log( geoip );
				
				break;

			default: 

				resp.writeHead( 404 );

				resp.write( 'Route note defined' );

				resp.end();

		}

	}

}