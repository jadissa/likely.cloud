$( document ).ready( function() {

    ( function run() {

        $('form[name=signup] select').change( function() {

        	if( $(this).val() != 'email' ) {

        		$('form input[type=email], form input[type=password]' ).parent().hide();

        	} else {

        		$('form input[type=email], form input[type=password]' ).parent().show();

        	}

        });

        $('form[name=signin] select').change( function() {

        	if( $(this).val() != 'email' ) {

        		$('form input[type=email], form input[type=password]' ).parent().hide();

        	} else {

        		$('form input[type=email], form input[type=password]' ).parent().show();

        	}

        });

    } )();

} );