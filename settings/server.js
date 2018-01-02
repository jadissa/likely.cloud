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

        "protocol"  : "http://",

        "domain"    : "likely.cloud",

        "port"      : 50451

    },

    "app": {

        "title"             : "likely.cloud(✿◠‿◠)ﾉ゛",

        "description"       : "Social media footprint connected properties",

        "sub_description"   : "Empowering users to control their online persona and build relevant connections",

        "keywords"          : "likely.cloud, social media footprint, connected, properties, realtime"

    },

    "api": {

        "response_type"     : "json",

        "timeout"           : 500,

        "discord"           : {

            "port"      : 50452,

            "entry"     : "/api/discord"

        }

    }

}

module.exports = settings;