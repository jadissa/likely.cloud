//
//  Force good developing habits
//
"use strict";


//
//  App settings
//
var _settings   = require( '../../settings/server' );


//
//  The MongoDB connection
//
var _mongoose   = require( 'mongoose' );


//
//  Database prefix
//
var _db_prefix   = _settings.server.persistence.prefix;


//
//  Database port
//
var _db_port    = _settings.server.persistence.port


//
// Connection URL
//
var _url        = 'mongodb://localhost:' + _db_port + '/' + _db_prefix + 'sessions';


//
//  Connection to session persistence
//
var connectPersistence  = function( ) {

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
    //  Bind errors
    //
    _db.on( 'error', console.error.bind( console, 'connection error: ' ) );


    //
    //  Bind persistence
    //
    _db.once( 'open', function( ) {

        if( _settings.server.dev ) {

            console.log( 'Connected to ' + _url );

        }

    });

    return _mongoose;

}


//
//  Saves object to database
//
var save    = function( _session ) {

    //
    //  Format
    //
    var _row    = {

        "ipaddress":    _session._ip,

        "email":        _session._email,

        "phone":        _session._phone

    };


    //
    //  Connect to persistence layer
    //
    var _instance = new connectPersistence( );


    //
    //  Define session schema
    //
    var _session_schema = _instance.Schema( {

        ipaddress   : _instance.Schema.Types.String,

        email       : { type: _instance.Schema.Types.String, lowercase: true },

        phone       : _instance.Schema.Types.String

    } );


    //
    //  Associate schema to connection session model
    //
    _instance.model( 'session', _session_schema );


    //
    //  Create new instance of our user model with relevant properties
    //
    var _session_row = new _instance.models.session( _row );


    //
    //  Save our instance
    //
    _session_row.save( function( err ) {

        if( err ) {

            console.error( 'Error while saving user session!' );

            console.error( err );
        }

    } ).then( function( _saved ) {

        //console.log( 'saved! ' + _saved );

        _instance.connection.close( );

    } );

}

module.exports  = {

    save: save

};