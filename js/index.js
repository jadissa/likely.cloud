$( document ).ready( function()
{
    //
    // Discord oauth
    //
    function handleDiscord( _formdata )
    {
        var result      = null;

        $.ajax( {
            url:        settings.discordOAuthURL,

            dataType:   settings.dataType,

            timeout:    settings.timeout,

            async:      settings.async,

            type:       "GET",

            data:
            {

            },
            success: function(_response)
            {

                console.log( _response );

                /*
                //
                //  @todo: determine all valid response formats 
                //  prior to reactivating these checks
                //
                if ( _response.stat.toLowerCase() != 'ok' )
                {

                    alert( _response.message );

                }
                else
                {

                    result = _response.message;

                }
                */

            },
            error: function( jq_xhr, status, error )
            {

                alert( error );

            }

        } );

        return result;
    }


    //
    //  Email signup
    //
    function handleEmail( _formdata )
    {
        var result      = null;

        $.ajax( {
            url:'50451:/api/email/login',

            url:        settings.discordOAuthURL,

            dataType:   settings.dataType,

            timeout:    settings.timeout,

            async:      settings.async,

            type:       "GET",

            data:
            {

                email:  $( _formdata ).prop( email ),


                //
                //  @todo: We're gonna need to be able to handle auths 
                //  once everyone signs up, ball starts rolling
                //

            },
            success: function(_response)
            {

                if ( _response.stat.toLowerCase() != 'ok' )
                {

                    alert(_response.message);

                }
                else
                {

                    result = _response.message;

                }

            },
            error: function( jq_xhr, status, error )
            {

                alert( error );

            }

        } );

        return result;
    }



    //
    //  Bad request
    //
    function handleFailure( _response )
    {

        //  @todo: unclear how these should be handled atm
        //  Need strat. We are posting to mongo

    }


    //
    //  Listen to login
    //
    function run( _settings )
    {

        $('form').submit( function( e )
        {

            var response;

            //
            // Switch on auth type
            //
            switch( $( this ).prop( 'login_type' ) )
            {
                //
                //  @todo: merge _settings and this to send off both as our request
                //


                //
                //  Discord oauth
                //
                case 'discord':

                    response = handleDiscord( $( this ) );

                break;


                //
                //  Email signup
                //
                case 'email':

                    response = handleEmail( $( this ) );

                break;


                //
                //  Invalid request
                //
                default:

                    handleFailure( '{"stat":"invalid"}' );

                break;

            }

            e.preventDefault();

        }

    }

    run( settings );

});