//
//  Force good developing habits
//
"use strict";


//
//  Express routing
//
var express         = require( 'express' );


//
//  Pathing
//
var path            = require( 'path' );


//
// Parsing
//
var parser          = require( 'body-parser' );


//
//  Header protection
//
var helmet          = require( 'helmet' );


//
//  Rate limit protection
//
var limit           = require( 'express-rate-limit' );


//
//  Cookie lib
//
var cookie          = require( 'cookie' );


//
//  Session libs
//
var session         = require('express-session');

//var session_store   = require( 'connect-mongo' )(session);


//
//  Utility lib
//
var util        = require( 'util' );


//
//  App settings
//
var settings    = require( './settings/server' );


//
//  Initialize app
//
var app = express( );


//
//  Initialize body parsing
//
app.use( parser.json() );

app.use( parser.urlencoded( { extended: false } ) );


//
//  The app will control/have access to the following files
//
app.use( express.static( path.join( __dirname + '/api' ) ) );

app.use( '/css', express.static( path.join( __dirname + '/css' ) ) );

app.use( '/images', express.static( path.join( __dirname + '/images' ) ) );

app.use( express.static( path.join( __dirname + '/logs' ) ) );

app.use( express.static( path.join( __dirname + '/models' ) ) );

app.use( express.static( path.join( __dirname + '/node_modules' ) ) );

app.use( express.static( path.join( __dirname + '/settings' ) ) );

app.use( express.static( path.join( __dirname + '/views' ) ) );


//
//  Use the helmet settings
//
app.use( helmet( ) );


//
//  Trust our proxy
//
app.enable('trust proxy');


//
//  Set max request limits
//
var limiter = new limit( {

    windowMs:   15 * 60 * 1000, // 15 minutes

    max:        100, // limit each IP to 100 requests per windowMs

    delayMs:    0 // disable delaying - full speed until the max limit is reached

} );


//
//  Apply limits to all requests
//
app.use( limiter );

/*
//
//  Initialize session
//
var mongoose = require('mongoose');
var connection = mongoose.createConnection(connectionOptions);
app.use( session( {

    secret  : settings.server.secret,

    store   : new session_store( { mongooseConnection: connection } )

} ) );


var store = new session_store( {

    host                        : settings.server.persistence.host,

    port                        : settings.server.persistence.port,

    db                          : settings.server.persistence.prefix + '_sessions',

    stringify                   : false,

    maxAge                      : 31536000,

    autoRemoveExpiredSession    : false,

    collection                  : 'sessions'

});

store.on( 'error', function( error ) {

    throw new Error( error.toString( ) );

} );

app.use( express.session( {

    secret  : settings.server.secret,

    key     : process.env.SERVER_KEY,

    cookie: {

        path        : '/',

        domain      : settings.server.domain,

        httpOnly    : true,

        maxAge      : 31536000

    },

    store: store

} ) );
*/


//
//  Handle index route
//
app.get( '/', function( req, res ) {

    //
    //  Initialize cookies
    //
    var _cookies = cookie.parse( req.headers.cookie || '' );

    if( !_cookies.likely ) {

        var _one_week = 7 * 24 * 3600 * 1000;

        res.cookie( 'likely', {

            domain      : settings.server.domain,

            encode      : 'encodeURIComponent',

            httpOnly    : true,

            secure      : false,

            signed      : false,

            sameSite    : true,

            path        : '/',

            expires     : new Date( Date.now() + _one_week ),

            maxAge      : _one_week

        } );

    }


    //
    //  Check session
    //
    //console.log( JSON.stringify(req.session) );

    if( !req.session || !req.session.userId ) {

        //return res.status( 403 ).send( { ok: false } );

    }


    //
    //  Initialize button registry
    //
    var _button_registry = {

        'register': {

            'login': {

                'label': 'Login',

                'markup': '<button class="button form-control btn-default">Login</button>'

            },

            'signup': {

                'label': 'Signup with Social Media',

                'markup': '<button class="button form-control btn-default">Signup</button>'

            }

        },

        'authenticated': {

            'discord': {

                'label': 'Login',

                'markup': '<button class="button form-control btn-default">Login</button>'

            }

        }

    };


    //
    //  Check if user registered
    //
    if( _cookies.consent ) {

        return res.redirect( '/s' );

    //
    //  Not registered
    //
    } else {

        var _buttons = '';

        for( var i in _button_registry.register ) {

            if( _button_registry.register[ i ].markup ) {

                if( _cookies.consent && _button_registry.register[ i ].label == 'policy' ) {

                    continue;

                }

                _buttons += _button_registry.register[ i ].markup;

            }

        }

    }


    //
    //  Load default template
    //
    return res.status( 200 ).set( 'Content-Type', 'text/html' ).render( 'index.ejs', {

        title: settings.app.title,

        description: settings.app.description,

        keywords: settings.app.keywords,

        copyright: settings.app.copyright,

        buttons: _buttons

    } );

} );


//
//  Handle policy form
//
app.get( '/policy', function( req, res ){

    //
    //  Initialize cookies
    //
    var _cookies = cookie.parse( req.headers.cookie || '' );


    //
    //  Initialize button registry
    //
    var _button_registry = {

        'register': {

            /*
            'age': {

                'label': 'Age',

                'markup': '<input id="ex6" name="age" type="text" data-slider-min="18" data-slider-max="150" data-slider-step="1" data-slider-value="18"/><span id="ex6CurrentSliderValLabel">&nbsp;<span id="ex6SliderVal">Age 18</span></span>'

            },
            */

            'agree': {

                'label': 'Legally Agree',

                'markup': '<button name="legal_constent" value=1 class="button form-control btn-default">Legally Agree</button>'

            },

            'disagree': {

                'label': 'No',

                'markup': '<button name="legal_constent" value=0 class="button form-control btn-default">No</button>'

            }

        },

        'authenticated': {

            'referrer': {

                'label': 'Back',

                'markup': '<button name="back" class="button form-control btn-default">Back</button>'

            }

        }

    };


    //
    // Check if user already consented
    //
    var _buttons = '';

    if( _cookies.consent ) {

        for( var i in _button_registry.authenticated ) {

            if( _button_registry.authenticated[ i ].markup ) {

                _buttons += _button_registry.authenticated[ i ].markup;

            }

        }


    //
    //  Not registered
    //
    } else {

        for( i in _button_registry.register ) {

            if( _button_registry.register[ i ].markup ) {

                _buttons += _button_registry.register[ i ].markup;

            }

        }

    }


    //
    //  Check if consent given
    //
    if( typeof req.query.legal_constent != 'undefined' && req.query.legal_constent == 1 ) {

        var _one_week = 7 * 24 * 3600 * 1000;

        res.cookie( 'consent', '1', {

            httpOnly: true,

            expires: new Date( Date.now() + _one_week ),

            maxAge: _one_week

        } );

        return res.redirect( '/social' );


    //
    //  Not given
    //
    } else if( typeof req.query.legal_constent != 'undefined' && req.query.legal_constent == 0 ) {

        return res.redirect('/');


    //
    //  Check if navigating backward
    //
    } else if ( typeof req.query.back != 'undefined' ) {

        return res.redirect( '/' );


    //
    //  Load default template
    //
    } else {

        return res.status( 200 ).set( 'Content-Type', 'text/html' ).render('terms.ejs', {

            title: settings.app.title,

            description: settings.app.description,

            keywords: settings.app.keywords,

            copyright: settings.app.copyright,

            buttons: _buttons

        });

    }

} );


//
//  Handle social integrations
//
app.get( '/social', function( req, res ) {

    //
    //  Initialize cookies
    //
    var _cookies = cookie.parse(req.headers.cookie || '');


    //
    //  Check if consent not given
    //
    if( ! _cookies.consent ) {

        return res.redirect( '/policy' );


    //
    //  Given
    //
    } else {

        var _request_ip     = require( 'request-ip' );

        var _ipaddress      = _request_ip.getClientIp( req );

        var _geoip          = require( 'geoip-lite' );

        var _d              = new Date;

        _d.toDateString();

        var _user_data    = {

            _ipaddress      : _ipaddress,

            _email          : '',

            _phone          : '',

            _geo            : _geoip.lookup( _ipaddress ),

            _consent        : 1,

            _status         : 1,

            _age            : 18,

            _datetime       : _d,

            _attributes     : [],

            _preferences    : [],

            _settings       : [],

            _services       : [],

            _statistics     : []

        };


        //
        //  The connection
        //
        var _user   = require( './models/databases/users.js' );


        //
        //  Save data to persistence
        //
        _user.save( _user_data );

        return res.redirect( '/s' );

    }

} );


//
//  Handle authenticated social
//
app.get( '/s', function( req, res ) {

    //
    //  Initialize cookies
    //
    var _cookies = cookie.parse(req.headers.cookie || '');


    //
    //  Check if consent not given
    //
    if( ! _cookies.consent ) {

        return res.redirect( '/policy' );


    //
    //  Load default template
    //
    } else {

        res.status( 200 ).set( 'Content-Type', 'text/html' ).render('s.ejs', {

            title: settings.app.title,

            description: settings.app.description,

            keywords: settings.app.keywords,

            copyright: settings.app.copyright

        } );

        return res.end( );

    }

} );


//
//  Capture all other requests
//
app.all( '*', function (req, res ) {

    return res.status( 400 ).redirect( '/' );

    /*
    // @todo: fix below code for files that actually exist
     //
     //  Load default template
     //
     res.status( 404 ).set( 'Content-Type', 'text/html' ).render( '404.ejs', {

     title: settings.app.title,

     description: settings.app.description,

     keywords: settings.app.keywords,

     copyright: settings.app.copyright

     } );

     return res.end( );
     */

} );


//
//  Listen on port for events
//
app.listen( 50451 );