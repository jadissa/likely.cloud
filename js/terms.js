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

        window.location = _request.form.geoip;

    }


	function run( _settings )
    {

        if( _settings.server.dev )
        {

            console.log("%cYou are running in dev mode", "background: pink; color: white; font-size: large");

            console.log("%cLogging settings below", "background: pink; color: white; font-size: large");

            console.log( _settings );

        }

        $( 'button[name=agree_terms]' ).click( function( e ) 
        {

        	var _agree_terms = $( this ).val();

        	var _request = mergeObjects( request(  mergeObjects( { "response": _agree_terms }, _settings ) ) );

        	if( _settings.server.dev )
	        {

	            console.log( _request );

	        }

        	var _response;

        	if( $(this).val() == 'no' )
        	{

        		_response = handleGoodbyes( _request );

        	}
        	else
        	{

        		_response = handleInit( _request );

        	}

        	e.preventDefault();

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