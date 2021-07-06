<?php
namespace Jeero\Calendars;

// Register new calendar.
register_calendar( __NAMESPACE__.'\\Very_Simple_Event_List' );

/**
 * Very_Simple_Event_List class.
 *
 * Adds support for the Very Simple Event List plugin.
 * @see: https://wordpress.org/plugins/very-simple-event-list/
 *
 * @since	1.2
 * 
 * @extends Calendar
 */
class Very_Simple_Event_List extends Post_Based_Calendar {

	function __construct() {
		
		$this->slug = 'Very_Simple_Event_List';
		$this->name = 'Very Simple Event List';
		$this->post_type = 'event';
		$this->categories_taxonomy = 'event_cat';
		
		parent::__construct();
		
	}
	
	/**
	 * Checks if this calendar is active.
	 * 
	 * @since	1.15
	 * @return	bool
	 */
	function is_active() {
		return function_exists( '\vsel_init' );
	}
	
	/**
	 * Processes event data from Inbox items.
	 * 
	 * @since	1.?
	 * @since	1.4	Added the subscription param.
	 *
	 * @param 	mixed 			$result
	 * @param 	array			$data		The structured data of the event.
	 * @param 	array			$raw		The raw data of the event.
	 * @param	string			$theater		The theater.
	 * @param	Subscription		$theater		The subscription.
	 * @return	int|WP_Error
	 */	 
	function process_data( $result, $data, $raw, $theater, $subscription ) {

		$result = parent::process_data( $result, $data, $raw, $theater, $subscription );
		
		if ( \is_wp_error( $result ) ) {			
			return $result;
		}
		
		$ref = $this->get_post_ref( $data );

		if ( $event_id = $this->get_event_by_ref( $ref, $theater ) ) {

			$event_start = $this->localize_timestamp( strtotime( $data[ 'start' ] ) );

			\update_post_meta( $event_id, 'event-date', $event_start );
			\update_post_meta( $event_id, 'event-start-date', $event_start );
			\update_post_meta( $event_id, 'event-time', date( 'H:i', $event_start ) );
			\update_post_meta( $event_id, 'event-link-label', __( 'Tickets', 'jeero' ) );
			\update_post_meta( $event_id, 'event-location', $data[ 'venue' ][ 'title' ] );
			\update_post_meta( $event_id, 'event-link', $data[ 'tickets_url' ] );
	
			if ( !empty( $data[ 'end' ] ) ) {
				$event_end = $this->localize_timestamp( strtotime( $data[ 'end' ] ) );	
				\update_post_meta( $event_id, 'event-date', $event_end );
			}

		}
		
		return $event_id;
		
		
	}
	
}