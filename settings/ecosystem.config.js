module.exports = {
    /**
     * Application configuration section
     * http://pm2.keymetrics.io/docs/usage/application-declaration/
     */
    apps : [

        // First application
        {
            name      : 'likely',
            script    : 'server.js',
            env: {
                COMMON_VARIABLE: 'true'
            },
            env_production : {
                NODE_ENV: 'production'
            },
            "watch": "./",
            "ignore_watch" : ["node_modules", "client/img", "likely.log", "out.log", "discord.log"],
            "watch_options": {
                "followSymlinks": false
            },
            "max_restarts": 3,
            "error_file"      : "logs/likely-error.log",
            "out_file"        : "logs/likely-out.log",
            "merge_logs"      : true,
            "log_date_format" : "YYYY-MM-DD HH:mm Z"
        },
         // Second application
         {
             name      : 'discord-token-generator',
             script    : 'node_modules/discord-token-generator/server.js',
             port      : "50452",
             "max_restarts": 3,
             "error_file"      : "logs/discord-error.log",
             "out_file"        : "logs/discord-out.log",
             "merge_logs"      : true,
             "log_date_format" : "YYYY-MM-DD HH:mm Z"
         }
    ]/*,

     // http://pm2.keymetrics.io/docs/usage/deployment/
     deploy : {
     production : {
     user : 'node',
     host : '212.83.163.1',
     ref  : 'origin/master',
     repo : 'git@github.com:repo.git',
     path : '/var/www/production',
     'post-deploy' : 'npm install && pm2 reload ecosystem.config.js --env production'
     },
     dev : {
     user : 'node',
     host : '212.83.163.1',
     ref  : 'origin/master',
     repo : 'git@github.com:repo.git',
     path : '/var/www/development',
     'post-deploy' : 'npm install && pm2 reload ecosystem.config.js --env dev',
     env  : {
     NODE_ENV: 'dev'
     }
     }
     }
     */
};