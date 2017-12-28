module.exports = {

    /**
     * Application configuration section
     * http://pm2.keymetrics.io/docs/usage/application-declaration/
     */

    apps : [

        // First application
        {
            name                    : 'likely',

            script                  : 'server.js',

            "env" : {

                "IS_DEV"            : true

            },

            "env_staging"           : {},

            "env_production"        : {},

            "watch"                 : "./",

            "ignore_watch"          : ["node_modules", "client/img", "likely.log", "logs"],

            "watch_options": {

                "followSymlinks"    : false

            },

            "max_restarts"          : 3,

            "autorestart"           : true,

            "error_file"            : "logs/likely-error.log",

            "out_file"              : "logs/likely-out.log",

            "merge_logs"            : true,

            "log_date_format"       : "YYYY-MM-DD HH:mm Z"

        }

    ]

};