$( document ).ready( function() {

    ( function run() {

        $('form').submit(function(e) {

            var submit_ok   = true;

            $.each( $('input[type=text], input[type=password]'), function() {

                console.log( $(this).val() );

                if( $(this).val() == '' || $(this).val() == 'undefined' ) {

                    submit_ok   = false;
                }

            });

            if( !submit_ok ) e.preventDefault();

        } );

    } )();

} );