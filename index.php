<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/bootstrap.php';

if( empty( $SETTINGS ) ) die( 'Improperly configured ' . __FILE__ );


//
//  Determine app status
//
if( $SETTINGS->visitor != $_SERVER['REMOTE_ADDR'] ) {

    exit( json_encode( ['stat' => false, 'message' => 'Check us out later!' ] ) );

}

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

    <script type="text/javascript"> //<![CDATA[
        var tlJsHost = ((window.location.protocol == "https:") ? "https://secure.comodo.com/" : "http://www.trustlogo.com/");
        document.write(unescape("%3Cscript src='" + tlJsHost + "trustlogo/javascript/trustlogo.js' type='text/javascript'%3E%3C/script%3E"));
        //]]>
    </script>

</head>

<body>

    <main class="centered">

<a href="/">

<pre>

<span class="pink">.__  .__ __          .__           </span><span class="blue">        .__                   .___</span>
<span class="pink">|  | |__|  | __ ____ |  | ___.__.  </span><span class="blue">   ____ |  |   ____  __ __  __| _/</span>
<span class="pink">|  | |  |  |/ // __ \|  |<   |  |  </span><span class="blue"> _/ ___\|  |  /  _ \|  |  \/ __ | </span>
<span class="pink">|  |_|  |    <\  ___/|  |_\___  |  </span><span class="blue"> \  \___|  |_(  <_> )  |  / /_/ | </span>
<span class="pink">|____/__|__|_ \\___  >____/ ____| /</span><span class="blue">\ \___  >____/\____/|____/\____ | </span>
<span class="pink">             \/    \/     \/      \</span><span class="blue">/     \/                       \/ </span>

</pre>

</a>

        <span class="description"><?= $SETTINGS->description ?></span>

        <span class="copyright"></span>

        <!--
        <span class="signups-description">Login using any of the active services</span>

        <span class="signups">
            <a href="<?= ( !empty( $SETTINGS->using_https ) ? 'https://' : 'http://' ) . $SETTINGS->api ?>/discord">Discord</a>
            <a href="<?= ( !empty( $SETTINGS->using_https ) ? 'https://' : 'http://' ) . $SETTINGS->api ?>/tumblr">Tumblr</a>
            <a href="<?= ( !empty( $SETTINGS->using_https ) ? 'https://' : 'http://' ) . $SETTINGS->api ?>/imgur">imgur</a>
        </span>
        -->

        <span class="message"><?= !empty( $_REQUEST['message'] ) ? $_REQUEST['message'] : null ?></span>

        <span class="policy"><a href="<?= ( !empty( $SETTINGS->using_https ) ? 'https://' : 'http://' ) . $SETTINGS->domain ?>/policy.php">Privacy Policy</a> <?= $SETTINGS->copyright ?></span>

    </main>

    <script type="text/javascript" src="//code.jquery.com/jquery-3.2.1.min.js"
            integrity="sha256-hwg4gsxgFZhOsEEamdOYGBf13FyQuiTwlAQgxVSNgt4="
            crossorigin="anonymous"></script>

    <script type="text/javascript" src="/js/controller.js"></script>

    <!--
    <script language="JavaScript" type="text/javascript">
        TrustLogo("http://likely.cloud/images/comodo_secure_seal_76x26_transp.png", "CL1", "none");
    </script>
    <a href="https://ssl.comodo.com" id="comodoTL">Comodo SSL</a>
    -->

</body>

</html>