var express = require('express');
var app = express();

app.set('views', __dirname + '/views');

app.use(express.static(__dirname));

/*
app.param('signup', function (req, res, next, signup) {
    console.log('CALLED ONLY ONCE');
    next();
});
*/

//  Handle root
app.get('/', function(req, res){

    res.render('index.ejs', {

        title: 'likely.cloud(✿◠‿◠)ﾉ゛',

        description: 'Realtime, instant connections',

        keywords: 'likely.cloud, realtime, instant connections'/*,

        nav: ['Home','About','Contact']*/

    });

});

//  Handle terms form
app.get('/terms', function(req, res){

    res.render('terms.ejs', {

        title: 'likely.cloud(✿◠‿◠)ﾉ゛Privacy Policy',

        description: 'Realtime, instant connections',

        keywords: 'likely.cloud, realtime, instant connections'

    });

});

//  Handle signup form
app.get('/signup', function(req, res){

    res.render('signup.ejs', {

        title: 'likely.cloud(✿◠‿◠)ﾉ゛Signup',

        description: 'Realtime, instant connections',

        keywords: 'likely.cloud, realtime, instant connections'

    });

});


//  Handle signup routes
app.get('/srt/:signup', function(req, res){

    console.log(req.params.signup)

    switch( req.params.signup ) {

        case 'discord':

            // @todo: call server listening point for discord-token-generator

            var discord = require('./node_modules/discord-token-generator/server.js')

            res.end()

            break

        default:

            res.send('Ok, route not defined')

            break

    }

    /*
    res.render('signup.ejs', {

        title: 'likely.cloud(✿◠‿◠)ﾉ゛Signup',

        description: 'Realtime, instant connections',

        keywords: 'likely.cloud, realtime, instant connections',

    });
    */

});

//  Handle login form
app.get('/login', function(req, res){

    res.render('login.ejs', {

        title: 'likely.cloud(✿◠‿◠)ﾉ゛Login',

        description: 'Realtime, instant connections',

        keywords: 'likely.cloud, realtime, instant connections'

    });

});

//  Handle geo form
app.get('/geo', function(req, res){

    var _request_ip = require('request-ip');

    var ip = _request_ip.getClientIp(req);

    /*
    var util = require('util');

    console.log(util.inspect(req.ip, {showHidden: false, depth: null}));
    */

    var geoip = require('geoip-lite');

    var geo = geoip.lookup(ip);

    console.log(geo);


    res.render('geo.ejs', {

        title: 'likely.cloud(✿◠‿◠)ﾉ゛About you',

        description: 'Realtime, instant connections',

        keywords: 'likely.cloud, realtime, instant connections',

        location: JSON.stringify(geo)

    });

});

app.listen(50451);