"use strict";

var mongoose        = require('mongoose');

var hash            = require( 'string-hash' );

var settings        = require( '../../settings/server' );

try {

    //
    //  Hash the db name from environment vars
    //
    var db_name     = hash( process.env.HOSTNAME + process.env.SERVER_ID );

    var url         = 'mongodb://localhost/users_' + db_name;


    //
    //  Define our user data model
    //
    var Schema = mongoose.Schema,
        ObjectId = Schema.ObjectId;

    var _user_schema = new Schema( {

        ipaddress   : String,

        geo         : Object,

        consent     : Number,

        date        : { type: Date, default: Date.now }

    });

    //
    //  Connect to mongodb
    //
    var _conn = mongoose.createConnection(url),

        _model = _conn.model('user', _user_schema),

        _user = new _model;

    _user.save();

    console.log( 'Connected to ' + url );

    /*
    // a setter
    _user.path( 'name' ).set( function( v ) {

        return v;

    } );

    // middleware
    _user.pre( 'save', function( next ) {

        notify( this.get( 'email') );

        next();
    });
    */

    var user = mongoose.model('user', _user_schema );


} catch ( error ) {

    console.log( error );

}

module.exports = mongoose;