var ip;

var http = require('http');

var geoip = require('geoip-lite');

var app = require( './routes' );

http.createServer( app.handleRequest).listen( 50451 );