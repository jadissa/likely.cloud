<?php

namespace App\controlers\lists;

use App\controlers\controler;

use App\models\user;

class feed extends controler {


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
	    //	Verify something to process
	    //
	    if( empty( $EXISTING_USERS ) ) {

	    	return $FEED_DATA;

	    }


	    //
	    //	Initialize feed
	    //
	    $FEED_DATA  = [
	        'SERVICE_REGISTRIES'   => [],
	    ];


	    //
	    //	Initialize dummy data
	    //
	    $GHOSTS					= ['you','fahad','Sarah♥','Syn2k','jamison','Vickie Vixen'];

	    $STATES					= ['California','Ohio','Nebraska','California','California','Texas'];

	    $existing_user_count	= sizeof( $EXISTING_USERS );


	    //
	    //	check if less than 5 registered users
	    //
	    while( $existing_user_count <= 5 ) {

	    	//
	    	//	Initialize
	    	//
	    	$user_id	= $existing_user_count++;


	    	//
	    	//	Resize ghosts
	    	//
	    	foreach( $EXISTING_USERS as $EXISTING_USER ) {

	    		if( $EXISTING_USER->id == $user_id ) {

	    			continue( 2 );

	    		}

	    	}
	    	

	    	//
	    	//	Verify ghost
	    	//
	    	if( empty( $GHOSTS[ $user_id ] ) ) {

	    		continue;

	    	}


	    	//
	    	//	Add ghost 
	    	//
	    	$NEW_USER				= new \stdClass;

	    	$NEW_USER->id 			= $user_id;

	    	$NEW_USER->sname 		= $GHOSTS[ $user_id ];

	    	$NEW_USER->geo 			= '{"state":"' . $STATES[ $user_id ] . '","country_name":"United States"}';

	    	$NEW_USER->created_at	= date( 'Y-m-d');

	    	$EXISTING_USERS->push( $NEW_USER );

	    }
	    

	    //
	    //	Build feed
	    //
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

	    return $FEED_DATA;

	}
	
}