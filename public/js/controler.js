$( document ).ready( function() {

    ( function run() {

        $('form[name=signup] select').change( function() {

        	if( $(this).val() != 'email' ) {

        		$('form input[type=email]' ).parent().hide();

        	} else {

        		$('form input[type=email]' ).parent().show();

        	}

        }).change();

        $('form[name=signin] select').change( function() {

        	if( $(this).val() != 'email' ) {

        		$('form input[type=email]' ).parent().hide();

        	} else {

        		$('form input[type=email]' ).parent().show();

        	}

        }).change();

    } )();

} );