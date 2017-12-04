var express = require('express');
var app = express();

app.set('views', __dirname + '/views');

app.use(express.static(__dirname));

var cookie = require('cookie');

var util = require('util');


//  Handle root
app.get('/', function(req, res) {

    // Parse the cookies on the request
    var _cookies = cookie.parse(req.headers.cookie || '');

    console.log(_cookies);

    var _button_registry = {
        'register': {
            'policy': {
                'label': 'policy',
                'markup': '<button class="button form-control btn-default" name="q" value="policy">Privacy Policy</button>'
            },
            'signup': {
                'label': 'Signup with Social Media',
                'markup': '<button class="button form-control btn-default" name="q" value="social">Signup</button>'
            }

        },
        'authenticated': {
            'discord': {
                'label': 'Login',
                'markup': '<button class="button form-control btn-default" name="media" value="discord">Login</button>'
            }

        }

    };


    //  Initialize buttons
    var _buttons = '';


    // Check if user registered
    if( _cookies.consent ) {

        for( i in _button_registry.authenticated ) {

            if( _button_registry.authenticated[i].markup ) {

                _buttons += _button_registry.authenticated[i].markup;

            }


        }

    } else {

        for( i in _button_registry.register ) {

            if( _button_registry.register[i].markup ) {

                if( _cookies.consent && _button_registry.register[i].label == 'policy' ) {

                    continue;

                }

                _buttons += _button_registry.register[i].markup;

            }

        }

    }

    res.render('index.ejs', {

        title: 'likely.cloud(✿◠‿◠)ﾉ゛',

        description: 'Realtime, instant connections',

        keywords: 'likely.cloud, realtime, instant connections',

        buttons: _buttons
        /*,

        nav: ['Home','About','Contact']*/

    });

});


//  Handle policy form
app.get('/policy', function(req, res){

    // Parse the cookies on the request
    var _cookies = cookie.parse(req.headers.cookie || '');

    console.log(req.query);

    if( req.query.legal_constent ) {

        res.setHeader('Set-Cookie', cookie.serialize('consent', '1', {

            httpOnly: true,

            maxAge: 60 * 60 * 24 * 7 // 1 week

        }));

        res.redirect( '/' );

    } else {

        res.render('terms.ejs', {

            title: 'likely.cloud(✿◠‿◠)ﾉ゛Privacy Policy',

            description: 'Realtime, instant connections',

            keywords: 'likely.cloud, realtime, instant connections',

        });



    }

});


//  Handle terms form
app.get('/terms', function(req, res){

    res.render('terms.ejs', {

        title: 'likely.cloud(✿◠‿◠)ﾉ゛Privacy Policy',

        description: 'Realtime, instant connections',

        keywords: 'likely.cloud, realtime, instant connections',

    });

});


//  Handle signup form
app.get('/signup', function(req, res){

    // Parse the cookies on the request
    var _cookies = cookie.parse(req.headers.cookie || '');

    console.log( _cookies.consent );

    if( ! _cookies.consent ) {

        res.redirect( '/policy' );

        res.end();

    }

    if( req.query.media && req.query.media == 'discord' ) {

        res.redirect('https://discordapp.com/channels/371854790440779776/371854790440779778');

        res.end();

    } else {

        res.render('signup.ejs', {

            title: 'Signup',

            description: 'Realtime, instant connections',

            keywords: 'likely.cloud, realtime, instant connections'

        });

    }

});


//  Handle social form
app.get('/social', function(req, res){

    // Parse the cookies on the request
    var _cookies = cookie.parse(req.headers.cookie || '');

    if( ! _cookies.consent ) {

        res.redirect( '/' );

        res.end();

    }

    var _request_ip = require('request-ip');

    var _ip = _request_ip.getClientIp(req);

    /*
     var util = require('util');

     console.log(util.inspect(req.ip, {showHidden: false, depth: null}));
     */

    var _geoip = require('geoip-lite');

    var _geo = _geoip.lookup(_ip);

    console.log(_geo);

    switch(req.query.type)
    {

        case 'discord':

            res.redirect('http://likely.cloud:50452/')

            break;

        default:
            break;

    }

});


//  Handle login form
app.get('/login', function(req, res){

    res.render('login.ejs', {

        title: 'likely.cloud(✿◠‿◠)ﾉ゛Login',

        description: 'Realtime, instant connections',

        keywords: 'likely.cloud, realtime, instant connections'

    });

});


app.listen(50451);