module.exports = {

    /**
     * Application configuration section
     * http://pm2.keymetrics.io/docs/usage/application-declaration/
     *
     * Changes to this file should NOT require a pm2 restart settings/ecosystem.config.js
     * After rebooting machine, watches may fail. If this happens, run the following commands
     * pm2 delete settings/ecosystem.config.js
     * pm2 start settings/ecosystem.config.js
     */

    apps : [

        // First application
        {
            "name"                  : "likely",

            "script"                : "server.js",

            "env" : {

                "IS_DEV"            : true

            },

            "env_staging"           : {},

            "env_production"        : {},

            "watch"                 : true,

            "ignore_watch"          : [ "images", "logs", "node_modules" ],

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