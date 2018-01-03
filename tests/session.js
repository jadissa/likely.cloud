//
//  Force good developing habits
//
"use strict";


//
//  The data
//
var _session_data   = {

    _ip       : '123.132.16.5',

    _email    : 'J@Likely.cloud',

    _phone    : '818-587-9212'

};


//
//  The connection
//
var _session   = require( '../models/databases/sessions.js' );


//
//  Save data to persistence
//
_session.save( _session_data );