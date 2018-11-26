$( document ).ready( function() {

    ( function run() {

        $( 'form[name=signup] select' ).change( function() {

        	if( $(this).val() != 'username' ) {

        		$( 'form input[type=email]' ).parent().hide();

        	} else {

        		$( 'form input[type=email]' ).parent().show();

        	}

        }).change();

        $( 'form[name=signin] select' ).change( function() {

        	if( $(this).val() != 'username' ) {

        		$( 'form input[type=email]' ).parent().hide();

        	} else {

        		$( 'form input[type=email]' ).parent().show();

        	}

        }).change();

        /*
        $( 'button[id=hidden_panes]' ).click( function() {

            $( '.hidden_panes' ).each( function() {

                if( $(this).is( ':visible' ) ) {

                    $(this).hide();

                    $(this).siblings( '.toggle_panes').each( function() {

                        if( $(this).is( ':visible' ) ) {

                            $(this).hide();

                        } else {

                            $(this).show();

                        }

                    } ) ;

                } else {

                    $(this).show();

                    $(this).siblings( '.toggle_panes').each( function() {

                        if( $(this).is( ':visible' ) ) {

                            $(this).hide();

                        } else {

                            $(this).show();

                        }

                    } ) ;

                }

            } );

        } );
        */

    } )();

} );