<?php
namespace Jeero\Calendars;

use Jeero\Helpers\Images as Images;

const JEERO_CALENDARS_ALL_IN_ONE_EVENT_CALENDAR_REF_KEY = 'jeero/all_in_one_event_calendar/ref';

// Register new calendar.
register_calendar( __NAMESPACE__.'\\All_In_One_Event_Calendar' );

/**
 * The_Events_Calendar class.
 * 
 * @extends Calendar
 */
class All_In_One_Event_Calendar extends Post_Based_Calendar {

	function __construct() {
		
		$this->slug = 'All_In_One_Event_Calendar';
		$this->name = __( 'All In One Event Calendar', 'jeero' );
		$this->categories_taxonomy = 'events_categories';
		
		parent::__construct();
		
	}
	
	function get_post_type( ) {
		return \AI1EC_POST_TYPE;
	}
	
	/**
	 * Checks if this calendar is active.
	 * 
	 * @since	1.15
	 * @return	bool
	 */
	function is_active() {
		return class_exists( '\Ai1ec_Front_Controller' );
	}
	
	/**
	 * Processes event data from Inbox items.
	 * 
	 * @since	1.0
	 * @since	1.4	Added the subscription param.
	 *
	 * @param 	mixed 			$result
	 * @param 	array			$data		The structured data of the event.
	 * @param 	array			$raw		The raw data of the event.
	 * @param	string			$theater		The theater.
	 * @param	Subscription		$theater		The subscription.
	 * @return 	Ai1ec_Event|WP_Error
	 */
	function process_data( $result, $data, $raw, $theater, $subscription ) {
		
		global $ai1ec_front_controller;
		
		$result = parent::process_data( $result, $data, $raw, $theater, $subscription);
		
		if ( \is_wp_error( $result ) ) {			
			return $result;
		}

		$ref = $this->get_post_ref( $data );

		if ( $post_id = $this->get_event_by_ref( $ref, $theater ) ) {

	        $args = array(
		        'start' => strtotime( $data[ 'start' ] ),
	            'ticket_url' => $data[ 'tickets_url' ],
	        );
        
	        if ( empty( $data[ 'end' ] ) ) {
				$args[ 'instant_event' ] =  true;
				$args[ 'end' ] = strtotime( $data[ 'start' ] ) + 15 * MINUTE_IN_SECONDS;	        
		    } else {
				$args[ 'end' ] = strtotime( $data[ 'end' ] );	        
	        }
        
			if ( !empty( $data[ 'venue' ] ) ) {
				$args[ 'venue' ] = $data[ 'venue' ][ 'title' ];
			}
		
			if ( !empty( $data[ 'prices' ] ) ) {
				$amounts = \wp_list_pluck( $data[ 'prices' ], 'amount' );
				$args[ 'cost' ]	 = min( $amounts );
			}

			$Ai1ec_Event = $ai1ec_front_controller->return_registry( true )->get( 'model.event', $args );		

			$Ai1ec_Event->set( 'post_id', $post_id );
			$Ai1ec_Event->set( 'post', get_post( $post_id ) );
			
			// Update event data.
			$success = $Ai1ec_Event->save( true );
			
			// Insert event data if update failed.
            if ( false === $success ) {
				$success = $Ai1ec_Event->save( false );
			}
			
		}

		return $post_id;
		
	}
	
}