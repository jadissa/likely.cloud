const express = require('express');

const path = require('path');

const app = express();

app.use('/static', express.static(path.join(__dirname, 'static')));

app.get('/', (req, res) => {

  res.status(200).sendFile(path.join(__dirname, 'index.html'));

});


//
// DigitalOcean Droplets require private IP
// https://www.digitalocean.com/community/tutorials/how-to-set-up-a-node-js-application-for-production-on-centos-7
//
app.set( 'host', '10.132.11.88' );

app.listen(50451, () => {

  console.info('Running on port 50451');

});

// Routes
app.use('/api/discord', require('./api/discord'));

app.use((err, req, res, next) => {

  switch (err.message) {

    case 'NoCodeProvided':

      console.error(err.stack);

      return res.status(400).send({

        status: 'ERROR',

        error: err.message,

      });

    default:

      return res.status(500).send({

        status: 'ERROR',

        error: err.message,

      });

  }

});