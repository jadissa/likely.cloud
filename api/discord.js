const express = require('express');

const fetch = require('node-fetch');

const btoa = require('btoa');

const util = require('util');

const { catchAsync } = require('../utils');

const router = express.Router();

const CLIENT_ID = process.env.CLIENT_ID;

const CLIENT_SECRET = process.env.CLIENT_SECRET;

const redirect = encodeURIComponent('http://likely.cloud:50451/api/discord/callback');


//
//  Authorize connection
//
router.get('/login', (req, res) => {

  res.redirect(`https://discordapp.com/oauth2/authorize?client_id=${CLIENT_ID}&scope=identify%20email%20guilds%20guilds.join&response_type=code&redirect_uri=${redirect}`);

});


//
//  Discord will redirect here after auth
//
router.get('/callback', catchAsync(async (req, res) => {

  if (!req.query.code) throw new Error('NoCodeProvided');

  const code = req.query.code;

  const creds = btoa(`${CLIENT_ID}:${CLIENT_SECRET}`);


  //
  //  Get the token
  //
  const token_response = await fetch(`https://discordapp.com/api/oauth2/token?grant_type=authorization_code&code=${code}&redirect_uri=${redirect}`,
    {

      method: 'POST',

        headers: {

          Authorization: `Basic ${creds}`,

        },

    });

    const token_json = await token_response.json();

    console.log(util.inspect(token_json, {sowHidden: false, depth: null}));


    //
    //  Get the user @todo: this block is failing with method not allowed error
    //
    const profile_response = await fetch(`https://discordapp.com/api/users/@me`,
    {

        method: 'GET',

        headers: {

          Authorization: `Bearer {{access_token}}`,

        },

    });

    const profile_json = await profile_response.json();

    console.log(util.inspect(profile_json, {sowHidden: false, depth: null}));

    res.redirect(`/?token=${token_json.access_token}`);

}));

module.exports = router;