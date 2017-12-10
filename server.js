var express = require( 'express' );

var app = express();

app.set( 'views', __dirname + '/views' );

app.set( 'models', __dirname + '/models' );

app.use( express.static( __dirname ) );

/*
function accessible( req, res ) {

    if( req.socket.remoteAddress !== '127.0.0.1' ) {

        res.writeHead( 403, { "Content-Type": "text/plain" } );

        res.write('403 Access Denied');

        return false;

    }

    return true;

}
*/
/*
var permittedLinker = ['localhost', '127.0.0.1'];  // who can link here?

app.use( function( req, res, next ) {

    var _i = 0, _notFound = 1, _referer = req.get( 'Referer' );

    if ( ( req.path==='/' ) || ( req.path==='' ) ) next(); // pass calls to '/' always

    if ( _referer ){

        while ( ( _i < permittedLinker.length ) && _notFound ){

            _notFound = ( _referer.indexOf(permittedLinker[ _i ] ) === - 1 );

            i++;

        }

    }

    if ( _notFound ) {

        res.redirect('/');

    } else {

        next(); // access is permitted, go to the next step in the ordinary routing

    }

} );
*/

var cookie      = require( 'cookie' );

var util        = require( 'util' );

var settings    = require( './settings/server' );

//  Handle root
app.get( '/', function( req, res ) {

    //if (! accessible( req, res) )

    // Parse the cookies on the request
    var _cookies = cookie.parse( req.headers.cookie || '' );

    if( settings.server.dev ) {

        console.log( settings );

    }

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


    //  Initialize buttons
    var _buttons = '';


    // Check if user registered
    if( _cookies.consent ) {

        for( i in _button_registry.authenticated ) {

            if( _button_registry.authenticated[ i ].markup ) {

                _buttons += _button_registry.authenticated[ i ].markup;

            }

        }

    } else {

        for( i in _button_registry.register ) {

            if( _button_registry.register[ i ].markup ) {

                if( _cookies.consent && _button_registry.register[ i ].label == 'policy' ) {

                    continue;

                }

                _buttons += _button_registry.register[ i ].markup;

            }

        }

    }

    res.render( 'index.ejs', {

        title: settings.app.title,

        description: settings.app.description,

        keywords: settings.app.keywords,

        buttons: _buttons
        /*,

        nav: ['Home','About','Contact']*/

    } );

} );


//  Handle policy form
app.get( '/policy', function( req, res ){

    // Parse the cookies on the request
    var _cookies = cookie.parse( req.headers.cookie || '' );

    var _button_registry = {

        'register': {

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


    //  Initialize buttons
    var _buttons = '';


    // Check if user already consented
    if( _cookies.consent ) {

        for( i in _button_registry.authenticated ) {

            if( _button_registry.authenticated[ i ].markup ) {

                _buttons += _button_registry.authenticated[ i ].markup;

            }

        }

    } else {

        for( i in _button_registry.register ) {

            if( _button_registry.register[ i ].markup ) {

                _buttons += _button_registry.register[ i ].markup;

            }

        }

    }

    if( typeof req.query.legal_constent != 'undefined' && req.query.legal_constent == 1 ) {

        var _one_week = 7 * 24 * 3600 * 1000;

        res.cookie( 'consent', '1', {

            httpOnly: true,

            expires: new Date( Date.now() + _one_week ),

            maxAge: _one_week

        } );

        res.redirect( '/social' );

        res.end();

    } else if( typeof req.query.legal_constent != 'undefined' && req.query.legal_constent == 0 ) {

        res.redirect('/');

        res.end();

    } else if ( typeof req.query.back != 'undefined' ) {

        res.redirect( '/' );

    } else {

        res.render('terms.ejs', {

            title: settings.app.title,

            description: settings.app.description,

            keywords: settings.app.keywords,

            buttons: _buttons

        });

    }

} );


//  Handle social integrations
app.get( '/social', function( req, res ) {

    // Parse the cookies on the request
    var _cookies = cookie.parse(req.headers.cookie || '');

    if( ! _cookies.consent ) {

        res.redirect( '/policy' );

        res.end();

    } else {

        var _request_ip = require( 'request-ip' );

        var _ip = _request_ip.getClientIp( req );

        console.log( _ip );

        var d = new Date;

        console.log( d.toDateString() );

        //console.log( util.inspect( req.ip, { showHidden: false, depth: null } ) );

        var _geoip = require( 'geoip-lite' );

        var _geo = _geoip.lookup( _ip );

        console.log( _geo );

        var db = require( './models/databases/users' );

        /*
        console.log(db.models);
        db.user.push( {

            "ipaddress": _ip,

            "geo": _geo,

            "consent": _cookies.consent

        } );
        console.log(db.models);
        */
        //var user = db.model('user', db.user);

        //var instance = new user();

        //console.log(instance);

        //db.user.path( 'ipaddress' ).set( _ip );

        res.redirect( settings.api.discordLoginURL );

        res.end();

    }

} );


// Handle authenticated social
app.get( '/s', function( req, res ) {

    // Parse the cookies on the request
    var _cookies = cookie.parse(req.headers.cookie || '');

    if( ! _cookies.consent ) {

        res.redirect( '/policy' );

        res.end();

    } else {

        res.end();

    }

} );


app.listen( 50451 );