<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/bootstrap.php';

if( empty( $SETTINGS ) ) die( 'Improperly configured ' . __FILE__ );


//
//  Determine app status
//
if( $SETTINGS->visitor != $_SERVER['REMOTE_ADDR'] ) {

    exit( json_encode( ['stat' => false, 'message' => 'Check us out later!' ] ) );

}

use \service\api;

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
<!--<div class="description heading">such as well might happen</div>-->
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

<form class="form" action="/request.php" method="POST">

    <div class="form-group">

        <input type="text" name="uname" id="uname" placeholder="Email" autocomplete="username" class="form-control">

    </div>

    <div class="form-group">

        <input type="password" name="pwd" id="pwd" placeholder="Password" autocomplete="new-password" class="form-control">

    </div>

    <div class="form-group">

        <input type="hidden" name="route" value="register" />

        <select name="validator" id="validator" class="form-control">

            <?php

            $REQUEST    = new api\request( $SETTINGS, '/services', 'GET', [ ] );

            $PARSED_RESPONSE    = $REQUEST->parse();

            foreach( $PARSED_RESPONSE->response->message as $SERVICE ) {

                if( !empty( $SERVICE->internal ) )  continue;

                if( !empty( $SERVICE->selected ) ) {

                    print '<option value="' . $SERVICE->name . '" selected="selected">' . ucfirst( $SERVICE->name ) . '</option>';

                } else {

                    print '<option value="' . $SERVICE->name . '">' . ucfirst( $SERVICE->name ) . '</option>';

                }


            }

            ?>

        </select>

    </div>

    <div class="form-group checkbox">

        <label for="status"><input type="checkbox" name="status" id="status" class="checkbox" checked="checked">Let others know where you're from!</label>

    </div>

    <div class="form-group">

        <input type="submit" name="submit" class="btn btn-info btn-md" value="submit">

    </div>

</form>

<div class="container feed">

<p class="slider notouch"><span>

<?php

$REQUEST            = new api\request( $SETTINGS, '/feed', 'GET', [] );

$PARSED_RESPONSE    = $REQUEST->parse();

foreach( $PARSED_RESPONSE->response->message as $USER_REGISTRY ) {

    print htmlentities( $USER_REGISTRY->string_data ) . '<br>';

}

?>

</p>

</div>

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

<span class="policy"><a href="<?= ( !empty( $SETTINGS->using_https ) ? 'https://' : 'http://' ) . $SETTINGS->domain ?>/policy.php">Privacy Policy</a> <?= $SETTINGS->copyright ?></span>

<script type="text/javascript" src="//code.jquery.com/jquery-3.2.1.min.js"
        integrity="sha256-hwg4gsxgFZhOsEEamdOYGBf13FyQuiTwlAQgxVSNgt4="
        crossorigin="anonymous"></script>

<script type="text/javascript" src="/js/controller.js"></script>

</main>

</body>

</html>
