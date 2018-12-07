<?php

namespace App\controlers\lists;

use App\controlers\controler;

use App\models\user;

class feed {


	/**
	 * 	Parses recent user registries into a list
	 *
	 * 	@return array
	 */
	public function getRecentRegistries() {

		//
	    //  Fetch existing users
	    //
	    $EXISTING_USERS  = user::fetchRecentRegistries();


	    //
	    //	Setup feed
	    //
	    $FEED_DATA  = [
	        'SERVICE_REGISTRIES'   => [],
	    ];

	    if( empty( $EXISTING_USERS ) ) {

	    	return $FEED_DATA;

	    }

		foreach( $EXISTING_USERS as $USER_FEED ) {

	        $FEED_DATA['SERVICE_REGISTRIES'][ $USER_FEED->id ]['string_data'] = $USER_FEED->sname . ' signed up from ';

	        $GEO = json_decode( $USER_FEED->geo );

	        if ( !empty( $GEO->state ) ) {

	            $FEED_DATA['SERVICE_REGISTRIES'][ $USER_FEED->id ]['string_data'] .= $GEO->state . ', ';

	        } else {

	            $FEED_DATA['SERVICE_REGISTRIES'][ $USER_FEED->id ]['string_data'] .= 'undisclosed location, ';

	        }

	        if( !empty( $GEO->country_name ) ) {

	            $FEED_DATA['SERVICE_REGISTRIES'][ $USER_FEED->id ]['string_data'] .= $GEO->country_name;

	        } else {

	            $FEED_DATA['SERVICE_REGISTRIES'][ $USER_FEED->id ]['string_data'] .= 'Nowhere\'sville';

	        }

	        $FEED_DATA['SERVICE_REGISTRIES'][ $USER_FEED->id ]['string_data'] .= ' during ' . date( 'F', strtotime( $USER_FEED->created_at ) ) . '!';

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

	    #print'<pre>';print_r( $USER_FEED );print'</pre>';exit;

	    return $FEED_DATA;

	}
	
}