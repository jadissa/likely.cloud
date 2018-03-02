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
//  Database host
//
var _db_host    = _settings.server.persistence.host;


//
//  Database port
//
var _db_port    = _settings.server.persistence.port;


//
//  Database user
//
var _db_user    = _settings.server.persistence.user;


//
//  Database pass
//
var _db_pass    = _settings.server.persistence.pass;


//
// Connection URL
//
var _url        = 'mongodb://' + _db_user + ':' + _db_pass + '@' + _db_host + ':' + _db_port + '/' + _db_prefix + 'users';


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
var save    = function( _user ) {

    //
    //  Format
    //
    var _row    = {

        "ipaddress"     : _user._ipaddress,

        "email"         : _user._email,

        "phone"         : _user._phone,

        "geo"           : _user._geo,

        "consent"       : _user._consent,

        "status"        : _user._status,

        "age"           : _user._age,

        "datetime"      : _user._datetime,

        "attributes"    : _user._attributes,

        "preferences"   : _user._preferences,

        "settings"      : _user._settings,

        "services"      : _user._services,

        "statistics"    : _user._statistics

    };


    //
    //  Connect to persistence layer
    //
    var _instance = new connectPersistence( );


    //
    //  Define user schema
    //
    var _user_schema = _instance.Schema( {

        ipaddress   : _instance.Schema.Types.String,

        email       : { type: _instance.Schema.Types.String, lowercase: true },

        phone       : _instance.Schema.Types.String,

        geo         : _instance.Schema.Types.Mixed,

        consent     : _instance.Schema.Types.Number,

        status      : _instance.Schema.Types.Number,

        age         : { type: _instance.Schema.Types.Number, min: 18, max: 150 },

        datetime    : _instance.Schema.Types.Date,

        attributes  : _instance.Schema.Types.Array,

        preferences : _instance.Schema.Types.Array,

        settings    : _instance.Schema.Types.Array,

        services    : _instance.Schema.Types.Array,

        statistics  : _instance.Schema.Types.Array

    } );


    //
    //  Associate schema to connection user model
    //
    _instance.model( 'user', _user_schema );


    //
    //  Create new instance of our user model with relevant properties
    //
    var _user_row = new _instance.models.user( _row );


    //
    //  Save our instance
    //
    _user_row.save( function( err ) {

        if( err ) {

            console.error( 'Error while saving user record!' );

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