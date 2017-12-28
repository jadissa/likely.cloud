//
//  Force good developing habits
//
"use strict";


//
//  Utility lib
//
var util        = require( 'util' );

var settings = {

    "server": {

        "dev"       : process.env.IS_DEV,

        "secret"    : process.env.SERVER_SECRET,

        "log": {

            "colors": {

                "info"  : "\x1b[32m",

                "warn"  : "\x1b[33m",

                "error" : "\x1b[31m"

            },

            "date"  : true

        }

    },

    "app": {

        "title"             : "likely.cloud(✿◠‿◠)ﾉ゛",

        "description"       : "Social media footprint connected properties",

        "sub_description"   : "Empowering users to control their online persona and build relevant connections",

        "keywords"          : "likely.cloud, social media footprint, connected, properties, realtime"

    },

    "api": {

        "dataType"          : "json",

        "timeout"           : 500,

        "discordLoginURL"   : "http://likely.cloud:50452/api/discord/login"

    }

}

module.exports = settings;