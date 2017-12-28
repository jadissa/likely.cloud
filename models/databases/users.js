//
//  Force good developing habits
//
"use strict";

var _settings   = require( '../../settings/server' );


//
//  Basic hash for connection
//
var _hash       = require( 'string-hash' );


//
//  The MongoDB connection
//
var _mongoose   = require( 'mongoose' );


//
//  Database name
//
var _db_name    = _hash( process.env.HOSTNAME + process.env.SERVER_ID );


//
// Connection URL
//
var _url        = 'mongodb://localhost:27017/users_' + _db_name;

try {

    //
    //  Connect to the server URL
    //
    _mongoose.connect( _url, { useMongoClient: true } );


    //
    //  Database instance
    //
    var _db                 = _mongoose.connection;


    //
    //  Promise
    //
    _mongoose.Promise       = global.Promise;


    //
    //  User schema
    //
    var _user = _mongoose.Schema( {

        ipaddress   : _mongoose.Schema.Types.String,

        email       : { type: _mongoose.Schema.Types.String, lowercase: true },

        phone       : _mongoose.Schema.Types.String,

        geo         : _mongoose.Schema.Types.Mixed,

        consent     : _mongoose.Schema.Types.Number,

        status      : _mongoose.Schema.Types.Number,

        age         : { type: _mongoose.Schema.Types.Number, min: 18, max: 150 },

        datetime    : _mongoose.Schema.Types.Date,

        attributes  : _mongoose.Schema.Types.Array,

        preferences : _mongoose.Schema.Types.Array,

        settings    : _mongoose.Schema.Types.Array,

        services    : _mongoose.Schema.Types.Array,

        statistics  : _mongoose.Schema.Types.Array

    } );


    //
    //  Bind errors
    //
    _db.on( 'error', console.error.bind( console, 'connection error:' ) );


    //
    //  Bind persistence
    //
    _db.once( 'open', function( ) {

        if( _settings.server.dev ) {

            console.log( 'Connected to ' + _url );

        }

    });

} catch( error ) {

    if( _settings.server.dev ) {

        console.log( error );

    }

}

module.exports =    _mongoose.model( 'user', _user );