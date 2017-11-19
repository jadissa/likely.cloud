$( document ).ready( function()
{
    //
    // Discord oauth
    //
    function handleDiscord( _request )
    {
        if( _request.form.discordLoginURL )
        {

            window.location = _request.form.discordLoginURL;

        }

    }


    //
    //  Email signup
    //
    function handleEmail( _request )
    {
        var result      = null;

        $.ajax( {
            url:        _request.form.emailURL,

            dataType:   _request.form.dataType,

            timeout:    _request.form.timeout,

            async:      _request.form.async,

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

                    alert( _response.message );

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

        if( _settings.server.dev )
        {

            console.log("%cYou are running in dev mode", "background: pink; color: white; font-size: large");

            console.log("%cLogging settings below", "background: pink; color: white; font-size: large");

            console.log( _settings );

        }

        var _terms = '/terms.html';

        $( '#open_agreement' ).click( function()
        {
            $('#modal_agreement').on( 'show', function()
            {

                $('iframe').attr( 'src', _terms);
              
            });

            $('#modal_agreement').modal( {show:true} )
        });

        $('form').submit( function( e )
        {

            var _response;

            if( _settings.server.dev )
            {

                try
                {

                    var _login_type = $( '[name=login_type]' ).val();

                    var _request = mergeObjects( request(  mergeObjects( { "login_type": _login_type }, _settings ) ) );

                    if( _settings.server.dev )
                    {

                        console.log( _request );

                    }

                }
                catch(e)
                {

                    if( _settings.server.dev )
                    {

                        console.log( _request );

                        e.preventDefault();

                        return;

                    }

                }

                e.preventDefault();

            }


            //
            // Switch on auth type
            //
            switch( _login_type )
            {

                //
                //  Discord oauth
                //
                case 'discord':

                    _response = handleDiscord( _request );

                break;


                //
                //  Email signup
                //
                case 'email':

                    _response = handleEmail( _request );

                break;


                //
                //  Invalid request
                //
                default:

                    handleFailure( '{"stat":"invalid"}' );

                break;

            }

            e.preventDefault();

            return _response;

        } );

    }


    //
    //  Load app
    //
    try
    {

        var response = run( settings );

    }
    catch( e )
    {

        console.log( e );

    }
    

} );