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

</head>

<body style="overflow-y: scroll;">

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

        <h4 class="alert-heading">Privacy Policy</h4>

        <strong>The information we collect</strong><br />
        We collect the media you voluntarily share with us while using our services for the purpose of building
        connections for you to persons who have identified shared similar media. Media here may refer to
        photos, videos, profiles, lifestyles, descriptions, usernames, geo locations, interests, internal and
        external associations, ethnicities, nationalities, religions, sexual orientations and connected services
        or associated properties. Properties here, refers to specific meta-data represented above or associated
        with that representation. We collect information from the devices used to access our services such as
        hardware versions, device settings, operating systems, device locations,device identifiers, timezones,
        mobile numbers and ip addresses. We collect information when you or connected services and users refer
        to, navigate toward or away from our service such as an advertisement an inbound link to likely.cloud or
        some third party service connection that correlates with our service.
        <br /><br />

        <strong>How we store information</strong><br />
        We use sessions and similar technologies to provide and support our services at the user level.
        Internally, we refer to these technologies to save your properties on our own systems or on third-party
        private systems.<br /><br />

        <strong>How we use information</strong><br />
        One of the goals of likely.cloud is to aggregate your social media footprint, allowing you to
        granularly understand online impressions of your persona, those you have connected with and the reverse.
        We cultivate an understanding of properties shared using our services to
        automatically indicate relevant connections to our users and to provide accurate tools related to the
        purpose of manually allowing users to connect with one another.<br /><br />

        <strong>Your rights</strong><br />
        You own all of the data you share with us and you control how it is shared with our other users.
        You give us permission, subject to removal, a non-exclusive, transferable, sub-license, royalty-free,
        worldwide license to use your properties for the purposes of understanding your data, sharing your data
        with other users and helping users grow their connections accordingly. The permission you give to us for any
        data you share ends when you request deletion of that data and we send receipt of its removal. Data is otherwise
        expunged after a certain time period, based on internal settings. Data shared publicly using our services may be collected by
        other entities. While using our services, your legal consent that is granted to likely.cloud that it may
        use, store and share your data, connecting you and relational users by proximity or other
        category of interest. Further legal consent on your part is that any data you have electively chosen to
        share with us may be stored, searched, accessed or shared by other users who agreed to these terms, our
        developers and third-party applications. These terms constitute a legal and binding agreement between the
        account holder and likely.cloud until such time your account is terminated.

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