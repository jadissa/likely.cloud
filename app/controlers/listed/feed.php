<?php

namespace App\controlers\listed;

use App\controlers\controler;

class feed extends controler {


	/**
	 * 	Renders the feed view
	 *
	 *	@param 	object 	$REQUEST
	 * 	@param 	object 	$RESPONSE
	 *
	 * 	@return bool
	 */
	public function getFeed( $REQUEST, $RESPONSE ) {

		//
	    //  Fetch existing users
	    //
	    $EXISTING_USERS  = $this->db->table( 'users as u' )
	    ->select( [
	        'u.id', 
	        'u.created', 
	        'ud.geo', 
	        'ud.status', 
	        'us.sname',
	        's.name',
	    ] )->join( 'user_data as ud', 'u.id', '=', 'ud.uid' )
	        ->join( 'user_services as us', 'ud.uid', '=', 'us.uid' )
	        ->join( 'services as s', 'us.sid', '=', 's.id' )
	    ->where( 'us.status', 'public' )
	    ->orderBy( 'u.created', 'desc' )
	    ->limit( 200 )
	    ->get();


	    //
	    //	Setup feed
	    //
	    $FEED_DATA  = [
	        'SERVICE_REGISTRIES'   => [],
	    ];

		foreach( $EXISTING_USERS as $USER_FEED ) {

	        $FEED_DATA['SERVICE_REGISTRIES'][ $USER_FEED['id'] ]['string_data'] = $USER_FEED['sname'] . ' signed up from ';

	        $GEO = json_decode($USER_FEED['geo']);

	        if ( !empty( $GEO->state ) ) {

	            $FEED_DATA['SERVICE_REGISTRIES'][ $USER_FEED['id'] ]['string_data'] .= $GEO->state . ', all the way out in ';

	        } else {

	            $FEED_DATA['SERVICE_REGISTRIES'][ $USER_FEED['id'] ]['string_data'] .= 'undisclosed location, all the way out in ';

	        }

	        if( !empty( $GEO->country_name ) ) {

	            $FEED_DATA['SERVICE_REGISTRIES'][ $USER_FEED['id'] ]['string_data'] .= $GEO->country_name;

	        } else {

	            $FEED_DATA['SERVICE_REGISTRIES'][ $USER_FEED['id'] ]['string_data'] .= 'Nowhere\'sville';

	        }

	        $FEED_DATA['SERVICE_REGISTRIES'][ $USER_FEED['id'] ]['string_data'] .= ' using ' . $USER_FEED['name'];

	        $FEED_DATA['SERVICE_REGISTRIES'][ $USER_FEED['id'] ]['string_data'] .= ' in ' . date( 'F', strtotime( $USER_FEED['created'] ) ) . '!';

	    }

	    /*
	    $DUMMY_FEED = [
	        'Syn2k signed up from undisclosed location, all the way out in Nowhere\'sville in November!',
	        'jamison signed up from Nevada, all the way out in United States in November!',
	        'fahad signed up from California, all the way out in United States in November!',
	    ];

	    $i = 999999999999;

	    foreach( $DUMMY_FEED as $dummy ) {

	        $FEED_DATA['SERVICE_REGISTRIES'][ $i ]['string_data']  = $dummy;

	        $i++;
	    }
	    */

	    return $FEED_DATA;

	}
	
}