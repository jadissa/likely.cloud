<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="utf-8">

    <title>Likely Cloud connected media</title>

    <meta name="description" content="Likely Cloud connected media" />

    <meta name="keywords" content="likely.cloud, irc, chat, discord, jadissa" />

    <meta http-equiv="Access-Control-Allow-Origin" content="*" />

    <meta name="format-detection" />

    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1, shrink-to-fit=no" />

    <link rel="shortcut icon" href="/images/favicon.ico" />

    <link rel="icon" sizes="16x16 32x32" href="/images/favicon.ico">

    <meta property="fb:app_id" content="1991908197750442" />

    <meta property="og:url" content="http://likely.cloud" />

    <meta property="og:title" content="Likely Cloud connected media" />

    <meta property="og:description" content="Likely Cloud connected media" />

    <meta property="og:image:height" content="118">

    <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css"
          integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u"
          crossorigin="anonymous">

    <!--<link rel="stylesheet" href="/css/layout.css">-->
    <style type="text/css">
        /* portrait orientation */
        /* ipad */
        @media screen
        and (min-width: 768px)
        and (max-width: 1024px)
        and (orientation:portrait)
        {
        }

        /* iphone 6 */
        @media screen
        and (min-width: 375px)
        and (max-width: 667px)
        and (orientation:portrait)
        {
        }

        /* iphone 5 & 5S */
        @media screen
        and (min-width:320px)
        and (max-width:568px)
        and (orientation:portrait)
        {
        }

        /* iphone 2G,3G,4,4S */
        @media screen
        and (min-width:320px)
        and (max-width:480px)
        and (orientation:portrait)
        {
        }

        *
        {

            padding:                0;

            margin:                 0;

            text-decoration:        none;

            background:             inherit;

            -webkit-box-sizing:     border-box;

            -moz-box-sizing:        border-box;

            box-sizing:             border-box;

            outline:                none;

            font-size:              7px;

        }

        body
        {

            background-color:       #272822;

            color:                  #fff;

            min-height:             100%;

            width:                  100%;

        }

        pre
        {

            margin: 0;

            padding: 0;

            border:                 0 !important;

            background-color:       #272822;

            font-size:              7px;

            color:                  #fff;

        }

        main
        {

            text-align: center;

        }

        .pink
        {

            color:                  magenta;

            font-size:              inherit;

        }

        .blue
        {

            color:                  dodgerblue;

            font-size:              inherit;

        }

        .centered
        {

            position: fixed;

            top: 50%;

            left: 50%;

            transform: translate(-50%, -50%);

        }

        .description
        {

            padding-top: 10px;

            font-size: 10px;

            display: block;

            clear: both;

        }

        .copyright
        {

            font-size: 10px;

            display: block;

            clear: both;

            text-align: left;

        }

    </style>
</head>

<body>

    <main class="centered">
<pre>
<span class="pink">.__  .__ __          .__           </span><span class="blue">        .__                   .___</span>
<span class="pink">|  | |__|  | __ ____ |  | ___.__.  </span><span class="blue">   ____ |  |   ____  __ __  __| _/</span>
<span class="pink">|  | |  |  |/ // __ \|  |<   |  |  </span><span class="blue"> _/ ___\|  |  /  _ \|  |  \/ __ | </span>
<span class="pink">|  |_|  |    <\  ___/|  |_\___  |  </span><span class="blue"> \  \___|  |_(  <_> )  |  / /_/ | </span>
<span class="pink">|____/__|__|_ \\___  >____/ ____| /</span><span class="blue">\ \___  >____/\____/|____/\____ | </span>
<span class="pink">             \/    \/     \/      \</span><span class="blue">/     \/                       \/ </span>
</pre>
        <span class="description">Realtime, instant connections and connected media properties &copy;2018 dreamloud</span>
        <span class="copyright"></span>
    </main>

    <script type="text/javascript" src="//code.jquery.com/jquery-3.2.1.min.js"
            integrity="sha256-hwg4gsxgFZhOsEEamdOYGBf13FyQuiTwlAQgxVSNgt4="
            crossorigin="anonymous"></script>

    <script type="text/javascript" src="/js/controller.js"></script>

</body>

</html>

<?php
$GEO = unserialize( file_get_contents( 'http://www.geoplugin.net/php.gp?ip=' . $_SERVER['REMOTE_ADDR'] ) );

$REQUEST_DATA   = array(
    'remote_address'    => $_SERVER['REMOTE_ADDR'],
    'user_agent'        => $_SERVER['HTTP_USER_AGENT'],
    'query'             => $_SERVER['QUERY_STRING'],
    'city'              => $GEO['geoplugin_city'],
    'state'             => $GEO['geoplugin_region'],
    'area_code'         => $GEO['geoplugin_areaCode'],
    'dma_code'          => $GEO['geoplugin_dmaCode'],
    'country_code'      => $GEO['geoplugin_countryCode'],
    'country_name'      => $GEO['geoplugin_countryName'],
    'continent_name'    => $GEO['geoplugin_continentName'],
    'latitude'          => $GEO['geoplugin_latitude'],
    'longitude'         => $GEO['geoplugin_longitude'],
    'timezone'          => $GEO['geoplugin_timezone'],
    'BROWSER'           => get_browser( null, true ),
);

$ch = curl_init();
curl_setopt( $ch, CURLOPT_URL, 'http://likely.cloud/api/' );
curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
curl_setopt( $ch,CURLOPT_POSTFIELDS, http_build_query( $REQUEST_DATA ) );
$response   = curl_exec( $ch );
#var_dump( $response );
curl_close( $ch );