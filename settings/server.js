var settings = {

    "server":       {

        "dev":              true,

        "secret":           "jDDuMemyMxM35RGXZA5b498g38Az6eG3",

        "log":      {

            "colors":       {

                "info":         "\x1b[32m",

                "warn":         "\x1b[33m",

                "error":        "\x1b[31m"

            },

            "date":         true

        }

    },

    "app":          {

        "title":            "likely.cloud(✿◠‿◠)ﾉ゛",

        "description":      "Realtime, instant connections",

        "keywords":         "likely.cloud, realtime, instant connections, social metrics"

        /*
        "fb:app_id":        "1991908197750442",

        "og:url":           "http://likely.cloud",

        "og:title":         "Realtime, instant connections",

        "og:description":   "Next gen social metrics"

        "og:image":         "http://likely.cloud/images/large-ad.png",

        "og:image:type":    "image/png",

        "og:image:width":   241,

        "og:image:height":  118
        */

    },

    "api":          {

        "dataType":         "json",

        "timeout":          500,

        "discordLoginURL":  "http://likely.cloud:50452/api/discord/login"

    }

}

module.exports = settings;