<?php
namespace Jeero\Calendars;

// Register new calendar.
register_calendar( __NAMESPACE__.'\\Events_Schedule_Wp_Plugin' );

/**
 * Events_Schedule_Wp_Plugin class.
 *
 * @since	1.0.3
 * 
 * @extends Calendar
 */
class Events_Schedule_Wp_Plugin extends Post_Based_Calendar {

	function __construct() {
		
		$this->slug = 'Events_Schedule_Wp_Plugin';
		$this->name = __( 'Events Schedule WP Plugin', 'jeero' );
		$this->post_type = 'class';
		$this->categories_taxonomy = 'wcs-type';
		
		parent::__construct();
		
	}

	/**
	 * Checks if this calendar is active.
	 * 
	 * @since	1.15
	 * @return	bool
	 */
	function is_active() {
		return defined( 'WCS_FILE' );
	}
	
	/**
	 * Processes event data from Inbox items.
	 * 
	 * @since	1.?
	 * @since	1.4		Added the subscription param.
	 * @since	1.6		Added support for import settings to decide whether to 
	 * 					overwrite title/description/image during import.
	 * 					Added support for post status settings during import.
	 *					Added support for venues.
	 *					Added support for categories.
	 * @since	1.17.2	Don't upload images again, since they are already uploaded in parent::process_data().
	 * @since	1.17.3	Fix incorrect start times.
	 *
	 * @param 	mixed 			$result
	 * @param 	array			$data		The structured data of the event.
	 * @param 	array			$raw		The raw data of the event.
	 * @param	string			$theater		The theater.
	 * @param	Subscription		$theater		The subscription.
	 */
	function process_data( $result, $data, $raw, $theater, $subscription ) {
		
		$result = parent::process_data( $result, $data, $raw, $theater, $subscription );
		
		if ( \is_wp_error( $result ) ) {			
			return $result;
		}
		
		$ref = $data[ 'ref' ];

		$event_start = strtotime( $data[ 'start' ] );

		if ( $event_id = $this->get_event_by_ref( $ref, $theater ) ) {
		
			\update_post_meta( $event_id, '_wcs_timestamp', $event_start );
			
			if ( empty( $data[ 'tickets_url' ] ) ) {
				\delete_post_meta( $event_id, '_wcs_action_label' );
				\delete_post_meta( $event_id, '_wcs_action_call' );
				\delete_post_meta( $event_id, '_wcs_action_custom' );
				\delete_post_meta( $event_id, '_wcs_interval' );
			} else {
				\update_post_meta( $event_id, '_wcs_action_label', __( 'Tickets', 'jeero' ) );
				\update_post_meta( $event_id, '_wcs_action_call', 1 );
				\update_post_meta( $event_id, '_wcs_action_custom', $data[ 'tickets_url' ] );
				\update_post_meta( $event_id, '_wcs_interval', 0 );			
			}
	
			if ( !empty( $data[ 'end' ] ) ) {
				$event_end = $this->localize_timestamp( strtotime( $data[ 'end' ] ) );	
				\update_post_meta( $event_id, '_wcs_duration', ( $event_end - $event_start ) / MINUTE_IN_SECONDS );
			}
			
			if ( !empty( $data[ 'production' ][ 'img' ] ) ) {
				
				\update_post_meta( $event_id, '_wcs_image', \get_the_post_thumbnail_url( $event_id ) );					
	
			}
			
			if ( empty( $data[ 'venue' ] ) && !empty( $data[ 'venue' ][ 'title' ] ) ) {
				\wp_set_object_terms( $event_id, array(), 'wcs-room', false  );			
			} else {
				\wp_set_object_terms( $event_id, $data[ 'venue' ][ 'title' ], 'wcs-room', false  );
			}

		}
		
		return $event_id;
		
	}
	
}