<?php
namespace Jeero\Calendars;

// Register new calendar.
register_calendar( __NAMESPACE__.'\\WP_Event_Manager' );

/**
 * WP_Event_Manager class.
 * @since	1.13
 * 
 * @extends Calendar
 */
class WP_Event_Manager extends Post_Based_Calendar {

	function __construct() {
		
		$this->slug = 'WP_Event_Manager';
		$this->name = __( 'WP Event Manager', 'jeero' );

		$this->post_type = 'event_listing';
		$this->categories_taxonomy = 'event_listing_category';		
		
		parent::__construct();
		
	}
	
	/**
	 * Gets all fields for this calendar.
	 * 
	 * @since	1.13
	 * @return	array
	 */
	function get_post_fields( ) {
		
		$fields = parent::get_post_fields( );
		
		$fields[] =	array(
			'name' => 'location',
			'title' => __( 'Event location', 'jeero' ),
			'template' => '{{ venue.title }}{% if venue.city %}, {{ venue.city }}{% endif %}',
		);

		return $fields;
		
	}

	function is_active() {
		return class_exists( '\WP_Event_Manager' );
	}
	
	/**
	 * Processes the data from an event in the inbox.
	 * 
	 * @since 	1.13
	 * @since	1.14		Added support for custom fields.	
	 *
	 */
	function process_data( $result, $data, $raw, $theater, $subscription ) {
		
		$result = parent::process_data( $result, $data, $raw, $theater, $subscription );
		
		if ( \is_wp_error( $result ) ) {			
			return $result;
		}
		
		$ref = $this->get_post_ref( $data );

		if ( $post_id = $this->get_event_by_ref( $ref, $theater ) ) {

			\update_post_meta( $post_id, '_registration', $data[ 'tickets_url' ] );
			
			if ( !empty( $data[ 'venue' ] ) ) {
				\update_post_meta( 
					$post_id, 
					'_event_venue_ids', 
					$this->get_post_id_by_title( $data[ 'venue' ][ 'title' ], 'event_venue' ) 
				);

				\update_post_meta( 
					$post_id, 
					'_event_location', 
					$this->get_rendered_template( 
						'location', 
						$data, 
						$subscription 
					) 
				);
				
			}
	
			$event_start = $this->localize_timestamp( strtotime( $data[ 'start' ] ) );
			\update_post_meta( $post_id, '_event_start_date', date( 'Y-m-d H:i:s', $event_start ) );
			\update_post_meta( $post_id, '_event_start_time', date( 'H:i:s', $event_start ) );
			
			if ( !empty( $data[ 'end' ] ) ) {
				$event_end = $this->localize_timestamp( strtotime( $data[ 'end' ] ) );			
				\update_post_meta( $post_id, '_event_end_date', date( 'Y-m-d H:i:s', $event_end ) );
				\update_post_meta( $post_id, '_event_end_time', date( 'H:i:s', $event_end ) );
			}
		
		}
		
		return $post_id;

	}

}