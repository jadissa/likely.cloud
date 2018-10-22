$( document ).ready( function() {

    ( function run() {

        console.log( this );

        $('form').submit(function(e) {

            e.preventDefault();

        } );

    } )();

} );