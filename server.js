//
//  Force good developing habits
//
"use strict";


//
//  Express routing
//
var express     = require( 'express' );


//
//  Header protection
//
var helmet      = require( 'helmet' );


//
//  Rate limit protection
//
var limit       = require( 'express-rate-limit' );


//
//  Cookie lib
//
var cookie      = require( 'cookie' );


//
//  Utility lib
//
var util        = require( 'util' );


//
//  App settings
//
var settings    = require( './settings/server' );


//
//  Initialization
//
var app = express( );

app.set( 'views', __dirname + '/views' );

app.set( 'models', __dirname + '/models' );

app.use( express.static( __dirname ) );

app.use( helmet( ) );

app.enable('trust proxy'); // only if you're behind a reverse proxy (Heroku, Bluemix, AWS if you use an ELB, custom Nginx setup, etc)

var limiter = new limit( {

    windowMs:   15 * 60 * 1000, // 15 minutes

    max:        100, // limit each IP to 100 requests per windowMs

    delayMs:    0 // disable delaying - full speed until the max limit is reached

} );


//
//  Apply limiter to all requests
//
app.use( limiter );


//
//  Handle index route
//
app.get( '/', function( req, res ) {

    //
    //  Initialize cookies
    //
    var _cookies = cookie.parse( req.headers.cookie || '' );


    //
    //  Check session
    //
    if(!req.session || !req.session.userId) {

        //return res.status( 403 ).send( { ok: false } );

    }


    //
    //  Log for developers
    //
    if( settings.server.dev ) {

        console.log( settings );

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

        res.redirect( '/s' );

        res.end();

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
    //  Display view
    //
    res.render( 'index.ejs', {

        title: settings.app.title,

        description: settings.app.description,

        keywords: settings.app.keywords,

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

        res.redirect( '/social' );

        res.end();


    //
    //  Not given
    //
    } else if( typeof req.query.legal_constent != 'undefined' && req.query.legal_constent == 0 ) {

        res.redirect('/');

        res.end();


    //
    //  Check if navigating backward
    //
    } else if ( typeof req.query.back != 'undefined' ) {

        res.redirect( '/' );


    //
    //  Display view
    //
    } else {

        res.render('terms.ejs', {

            title: settings.app.title,

            description: settings.app.description,

            keywords: settings.app.keywords,

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

        res.redirect( '/policy' );

        res.end();


    //
    //  Given
    //
    } else {

        var _request_ip     = require( 'request-ip' );

        var _ip             = _request_ip.getClientIp( req );

        var _geoip          = require( 'geoip-lite' );

        var _geo            = _geoip.lookup( _ip );

        var _d              = new Date;

        _d.toDateString();

        var _user = require( './models/databases/users' );

        var _row    = {

            "ipaddress":    _ip,

            "email":        '',

            "phone":        '',

            "geo":          _geo,

            "consent":      _cookies.consent,

            "status":       1,

            "age":          18,

            "datetime":     _d,

            "attributes":   [],

            "preferences":  [],

            "settings":     [],

            "services":     [],

            "statistics":   []

        };


        //
        //  Record user entry
        //
        try {

            var _user_row = new _user( _row );

            if( settings.server.dev ) {

                console.log( util.inspect( _user_row, { showHidden: false, depth: null } ) );

            }

            _user_row.save( function( err ) {

                if( err ) {

                    console.log( 'Error on save!' );

                    console.log( err );
                }

            } ).then( function( _d ) {

                console.log( 'saved! ' + _d );

            });

        } catch( error ) {

            console.log( err );

        }

        res.redirect( settings.api.discordLoginURL );

        res.end();

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

        res.redirect( '/policy' );

        res.end();


    //
    //  Given
    //
    } else {

        res.render('s.ejs', {

            title: settings.app.title,

            description: settings.app.description,

            keywords: settings.app.keywords

        });

    }

} );


//
//  Listen on port for events
//
app.listen( 50451 );