$( document ).ready( function()
{
    //
    //  Signups
    //
    function handleSignups( _request )
    {
        if (_request.form.discordLoginURL)
        {

            window.location = '/signup';

        }

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

        $('form').submit( function( e )
        {

            var _response;

            if( _settings.server.dev )
            {

                try
                {

                    var _login_type = $( '[name=signup]' ).val();

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
                //  Signups
                //
                case '/signup':

                    _response = handleSignups( _request );

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