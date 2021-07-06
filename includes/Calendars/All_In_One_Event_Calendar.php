<?php
namespace Jeero\Calendars;

// Register new calendar.
register_calendar( __NAMESPACE__.'\\All_In_One_Event_Calendar' );

/**
 * All_In_One_Event_Calendar class.
 *
 * @since	1.0
 * @since	1.16	Now extends Post_Based_Calendar.
 * 
 * @extends Post_Based_Calendar
 */
class All_In_One_Event_Calendar extends Post_Based_Calendar {

	function __construct() {
		
		$this->slug = 'All_In_One_Event_Calendar';
		$this->name = __( 'All In One Event Calendar', 'jeero' );
		$this->categories_taxonomy = 'events_categories';
		
		parent::__construct();
		
	}
	
	/**
	 * Gets all post fields for events.
	 * 
	 * @since	1.16
	 * @return	string[]		All post fields for events.
	 */
	function get_post_fields() {
		$post_fields = parent::get_post_fields();
		
		$new_post_fields = array();
		
		foreach( $post_fields as $post_field ) {
			
			// All_In_One_Event_Calendar does not support excerpts.
			if ( 'excerpt' == $post_field[ 'name' ] ) {
				continue;
			}
			
			$new_post_fields[] = $post_field;
			
		}
		
		return $new_post_fields;
		
	}
	
	/**
	 * Gets the post type slug for events.
	 * 
	 * @since	1.16
	 * @return	string	The post type slug for events.
	 */
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
	 * @since	1.4		Added the subscription param.
	 * @since	1.16		Rewrite to match the new Post_Based_Calendar::process_data().
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

		$post_id = $this->get_event_by_ref( $ref, $theater );

		$Ai1ec_Event = $ai1ec_front_controller->return_registry( true )->get( 'model.event' );

		// Try to load existing event data from 	custom Ai1 table.
		try {
			$Ai1ec_Event->initialize_from_id( $post_id );
		} catch( \Ai1ec_Event_Not_Found_Exception $e ) {
			// No event data found. Create an empty row.
			$Ai1ec_Event->set( 'post_id', $post_id );
			$Ai1ec_Event->save( false );
		}
		
		$Ai1ec_Event->set( 'start', strtotime( $data[ 'start' ] ) );
		$Ai1ec_Event->set( 'ticket_url', $data[ 'tickets_url' ] );
		
        if ( empty( $data[ 'end' ] ) ) {
			$Ai1ec_Event->set( 'instant_event', true );
			$Ai1ec_Event->set( 'end', strtotime( $data[ 'start' ] ) + 15 * MINUTE_IN_SECONDS );
	    } else {
			$Ai1ec_Event->set( 'end', strtotime( $data[ 'end' ] ) );
        }
    
		if ( !empty( $data[ 'venue' ] ) ) {
			$Ai1ec_Event->set( 'venue', $data[ 'venue' ][ 'title' ] );
		}
	
		if ( !empty( $data[ 'prices' ] ) ) {
			$amounts = \wp_list_pluck( $data[ 'prices' ], 'amount' );
			$Ai1ec_Event->set( 'cost', min( $amounts ) );
		}

		$Ai1ec_Event->set( 'post', get_post( $post_id ) );
		
		// Update event data.
		$success = $Ai1ec_Event->save( true );
					
		return $post_id;
		
	}
	
}