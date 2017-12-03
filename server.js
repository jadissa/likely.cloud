var express = require('express');
var app = express();

app.set('views', __dirname + '/views');

app.use(express.static(__dirname));


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

        keywords: 'likely.cloud, realtime, instant connections',

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


//  Handle social form
app.get('/social', function(req, res){

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