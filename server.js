var express = require('express');
var app = express();

app.set('views', __dirname + '/views');

app.use(express.static(__dirname));


app.get('/', function(req, res){

    res.render('index.ejs', {

        title: 'likely.cloud(✿◠‿◠)ﾉ゛',

        description: 'Realtime, instant connections',

        keywords: 'likely.cloud, realtime, instant connections'/*,

        nav: ['Home','About','Contact']*/

    });

});

app.get('/terms', function(req, res){

    res.render('terms.ejs', {

        title: 'likely.cloud(✿◠‿◠)ﾉ゛Privacy Policy',

        description: 'Realtime, instant connections',

        keywords: 'likely.cloud, realtime, instant connections',

    });

});

app.get('/signup', function(req, res){

    res.render('signup.ejs', {

        title: 'likely.cloud(✿◠‿◠)ﾉ゛Signup',

        description: 'Realtime, instant connections',

        keywords: 'likely.cloud, realtime, instant connections',

    });

});

app.get('/login', function(req, res){

    res.render('login.ejs', {

        title: 'likely.cloud(✿◠‿◠)ﾉ゛Login',

        description: 'Realtime, instant connections',

        keywords: 'likely.cloud, realtime, instant connections',

    });

});

app.listen(50451);