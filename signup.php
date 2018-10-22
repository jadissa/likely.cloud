<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/bootstrap.php';

if( empty( $SETTINGS ) ) die( 'Improperly configured ' . __FILE__ );

?>
    <!DOCTYPE html>

    <html lang="en">

    <head>

        <meta charset="utf-8">

        <title><?= $SETTINGS->title ?></title>

        <meta name="description" content="<?= $SETTINGS->description ?>" />

        <meta name="keywords" content="<?= $SETTINGS->keywords ?>" />

        <meta http-equiv="Access-Control-Allow-Origin" content="*" />

        <meta name="format-detection" />

        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1, shrink-to-fit=no" />

        <link rel="shortcut icon" href="/images/favicon.ico" />

        <link rel="icon" sizes="16x16 32x32" href="/images/favicon.ico">

        <?php if( !empty( $SETTINGS->social[0]->facebook ) && !empty( $SETTINGS->social[0]->facebook[0]->enabled) ) { ?>

            <meta property="fb:app_id" content="<?= $SETTINGS->social[0]->facebook[0]->app_id ?>" />

            <meta property="og:url" content="<?= !empty( $SETTINGS->using_https ) ? 'https://' : 'http://' . $SETTINGS->domain ?>" />

            <meta property="og:title" content="<?= $SETTINGS->title ?>" />

            <meta property="og:description" content="<?= $SETTINGS->description ?>" />

            <meta property="og:image:height" content="118">

        <?php } ?>

        <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css"
              integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u"
              crossorigin="anonymous">

        <link rel="stylesheet" href="/css/layout.css">

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

        <span class="description"><?= $SETTINGS->description ?></span>

        <span class="copyright"></span>

        <span class="signups">Join the conversation using <a href="<?= !empty( $SETTINGS->using_https ) ? 'https://' : 'http://' . $SETTINGS->api ?>/discord">Discord</a></span>

        <span class="message"><?= !empty( $_REQUEST['message'] ) ? $_REQUEST['message'] : null ?></span>

    </main>

    <script type="text/javascript" src="//code.jquery.com/jquery-3.2.1.min.js"
            integrity="sha256-hwg4gsxgFZhOsEEamdOYGBf13FyQuiTwlAQgxVSNgt4="
            crossorigin="anonymous"></script>

    <script type="text/javascript" src="/js/controller.js"></script>

    </body>

    </html>

<?php

$API_URL = !empty( $SETTINGS->using_https ) ? 'https://' : 'http://' . $SETTINGS->api . '/ping';

if( !empty( $SETTINGS->debug ) ) {

    var_dump($API_URL);

}

$ch = curl_init();

curl_setopt( $ch, CURLOPT_URL, $API_URL );

curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );

curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( [
    '_SERVER' => $_SERVER,
    '_HEADERS' => getallheaders(),
    '_REQUEST' => $_REQUEST,
] ) );

$response   = curl_exec( $ch );

if( !empty( $SETTINGS->debug ) ) {

    var_dump( curl_getinfo( $ch, CURLINFO_HTTP_CODE ), $response );

}

curl_close( $ch );