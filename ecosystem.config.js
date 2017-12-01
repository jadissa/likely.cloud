module.exports = {
    /**
     * Application configuration section
     * http://pm2.keymetrics.io/docs/usage/application-declaration/
     */
    apps : [

        // First application
        {
            name      : 'likely.cloud',
            script    : 'server.js',
            env: {
                COMMON_VARIABLE: 'true'
            },
            env_production : {
                NODE_ENV: 'production'
            },
            "watch": "./",
            "ignore_watch" : ["node_modules", "client/img"],
            "watch_options": {
                "followSymlinks": false
            }
        },

        /*
         // Second application
         {
         name      : 'WEB',
         script    : 'web.js'
         }
         */
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