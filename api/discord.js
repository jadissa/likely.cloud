const express = require('express');

const fetch = require('node-fetch');

const btoa = require('btoa');

const util = require('util');

//
//  The discord consumer
//  Authenticates a user and logs them into our Discord channel
//  During this process, we capture their Discord registration `email` and `username`
//  extract this information and use it internally for our own registration
//
//  @todo: It's going to eventually be necessary to handle negative response codes
//  eg. 429 (TOO MANY REQUESTS)
//  Reference: https://discordapp.com/developers/docs/topics/response-codes
//

const { catchAsync } = require('../utils');

const router = express.Router();

const CLIENT_ID = process.env.CLIENT_ID;

const CLIENT_SECRET = process.env.CLIENT_SECRET;

const INVITE_CODE = process.env.INVITE_CODE;

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

  //console.log(util.inspect(token_json, {sowHidden: false, depth: null}));

  //console.log(util.inspect(token_json["access_token"], {sowHidden: false, depth: null}));


  //
  //  Get the user
  //
  const user_response = await fetch(`https://discordapp.com/api/users/@me`,
    {

      method: 'GET',

      headers: {

        Authorization: `Bearer ` + token_json.access_token,

      },

  });

  const user_json = await user_response.json();

  console.log(util.inspect(user_json, {sowHidden: false, depth: null}));


  //
  //  Get the invite
  //
  const invite_response = await fetch(`https://discordapp.com/api/invites/${INVITE_CODE}`,
  {
    
      method: 'GET',
      
      headers: {
        
        Authorization: `Bearer ` + token_json.access_token,
        
      },
      
  });
  
  const invite_json = await invite_response.json();

  //console.log(util.inspect(invite_json, {sowHidden: false, depth: null}));


  //
  //  Accept the invite
  //
  const join_response = await fetch(`https://discordapp.com/api/invites/${INVITE_CODE}`,
  {

    method: 'POST',

      headers: {

        Authorization: `Bearer ` + token_json.access_token,

      },

  });

  const join_json = await join_response.json();

  console.log(util.inspect(join_json, {sowHidden: false, depth: null}));

  //
  //  Redirect user to homepage
  //
  response.writeHead(302, {
    'Location': '/'
    //add other headers here...
  });
  response.end();

}));

module.exports = router;