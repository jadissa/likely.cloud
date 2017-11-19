$( document ).ready( function()
{
	function handleGoodbyes( _request )
    {
        if( _request.response )
        {

            window.location = '/';

            return;

        }

    }


    function handleInit( _request )
    {

    	if( _request.response != 'yes' )
    	{

    		window.location = '/';

    		return;

    	}

    	console.log( 'the user wishes to proceed' );

    }


	function run( _settings )
    {

        if( _settings.server.dev )
        {

            console.log("%cYou are running in dev mode", "background: pink; color: white; font-size: large");

            console.log("%cLogging settings below", "background: pink; color: white; font-size: large");

            console.log( _settings );

        }

        $( 'button[name=agree_terms]' ).click( function() 
        {

        	var _response;

        	if( $(this).val() == 'no' )
        	{

        		_response = handleGoodbyes( {"response":"no"} );

        	}
        	else
        	{

        		_response = handleInit( {"response":"yes"} );

        	}

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