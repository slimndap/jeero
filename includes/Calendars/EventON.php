<?php
namespace Jeero\Calendars;

// Register new calendar.
register_calendar( __NAMESPACE__.'\\EventON' );

/**
 * EventON class.
 * @since	1.15
 * @since	1.16	Now extends Post_Based_Calendar.
 * 
 * @extends Post_Based_Calendar
 */
class EventON extends Post_Based_Calendar {

	function __construct() {
		
		$this->slug = 'EventON';
		$this->name = __( 'EventON', 'jeero' );
		$this->post_type = 'ajde_events';
		
		parent::__construct();

	}
	
	/**
	 * Checks if this calendar is active.
	 * 
	 * @since	1.15
	 * @return	bool
	 */
	function is_active() {
		return class_exists( '\EventON' );
	}

	/**
	 * Gets the category taxonomy slug for events.
	 * 
	 * @sicne	1.16
	 * @param 	Jeero\Subscription	$subscription
	 * @return	string				The category taxonomy slug for events.
	 */
	function get_categories_taxonomy( $subscription ) {
		return $this->get_setting( 'import/category_taxonomy', $subscription, 'event_type' );
	}

	/**
	 * Gets an EventON post ID by Jeero ref.
	 * 
	 * @since	1.15
	 * @since	1.18		@uses \Jeero\Calendars\Calendar::log().
	 *
	 * @param 	string			$ref
	 * @param 	string 			$theater
	 * @return	int|bool						The event post ID or <false> if not found.
	 */
	function get_event_by_ref( $ref, $theater ) {
		
		$this->log( sprintf( 'Looking for existing %s item %s.', $theater, $ref ) );
		
		$args = array(
			'post_type' => 'ajde_events',
			'post_status' => 'any',
			'meta_query' => array(
				array(
					'key' => $this->get_ref_key( $theater ),
					'value' => $ref,					
				),
			),
		);
		
		$events = \get_posts( $args );
		
		if ( empty( $events ) ) {
			return false;
		}
		
		return $events[ 0 ]->ID;
		
	}

	/**
	 * Gets all fields for this calendar.
	 * 
	 * @since	1.15
	 * @return	array
	 */
	function get_fields( $subscription ) {
		
		$fields = parent::get_fields( $subscription );
		
		$fields = array_merge( $fields, $this->get_import_status_fields() );


		$fields = array_merge( $fields, $this->get_import_update_fields() );

		$fields = array_merge( $fields, $this->get_custom_fields_fields( $subscription ) );
		
		$new_fields = array();
		
		foreach( $fields as $field )  {

			$new_fields[] = $field;
			
			if ( 'calendar' == $field[ 'name' ] ) {

				$new_fields[] = array(
					'name' => sprintf( '%s/import/category_taxonomy', $this->slug ),
					'label' => __( 'Import categories as ', 'jeero' ),
					'type' => 'select',
					'choices' => array(
						'post_tag' => __( 'Tags' ),
						'event_type' => __( 'Event Type', 'jeero' ),
						'event_type_2' => __( 'Event Type 2', 'jeero' ),
					),
				);
				
			}
			
		}

		return $new_fields;		
		
	}
	
	/**
	 * Gets all post fields for events.
	 * 
	 * @since	1.16
	 * @return	string[]		All post fields for events.
	 */
	function get_post_fields() {
		$post_fields = parent::get_post_fields();
		
		$post_fields[] = array(
			'name' => 'subtitle',
			'title' => __( 'Event subtitle', 'jeero' ),
			'template' => '',
		);
		
		return $post_fields;
	}
	
	/**
	 * Gets the EventON location ID by location title.
	 *
	 * Creates a new location if no location is found.
	 * 
	 * @since	1.15
	 * @param 	string	$title
	 * @return	int
	 */
	function get_location_id( $title ) {
		$location_id = wp_cache_get( $title, 'jeero/location_id' );

		if ( false === $location_id ) {
		
			$location_term = get_term_by( 'name', $title, 'event_location' );
			
			if ( !( $venue_post ) ) {
				$location_id = tribe_create_venue( 
					array( 
						'Venue' => $title,
					)
				);
			} else {
				$location_id = $location_post->ID;
			}
			
			wp_cache_set( $title, $location_id, 'jeero/location_id' );
			
		}
		
		return $location_id;		
	}
	
	/**
	 * Processes the data from an event in the inbox.
	 * 
	 * @since 	1.15
	 * @since	1.16		Rewrite to match the new Post_Based_Calendar::process_data().
	 *
	 * @return	int|WP_Error		The event ID or a WP_Error is there was a problem.
	 */
	function process_data( $result, $data, $raw, $theater, $subscription ) {
		
		$result = parent::process_data( $result, $data, $raw, $theater, $subscription );
		
		if ( \is_wp_error( $result ) ) {			
			return $result;
		}
		
		$ref = $this->get_post_ref( $data );

		$event_id = $this->get_event_by_ref( $ref, $theater );

		$event_start = $this->localize_timestamp( strtotime( $data[ 'start' ] ) );
		\update_post_meta( $event_id, '_start_hour', date( 'g', $event_start ) );
		\update_post_meta( $event_id, '_start_minute', date( 'I', $event_start ) );
		\update_post_meta( $event_id, '_start_ampm', date( 'a', $event_start ) );
		\update_post_meta( $event_id, 'evcal_srow', $event_start );
				
		if ( !empty( $data[ 'end' ] ) ) {
			$event_end = $this->localize_timestamp( strtotime( $data[ 'end' ] ) );
			\update_post_meta( $event_id, '_end_hour', date( 'g', $event_end ) );
			\update_post_meta( $event_id, '_end_minute', date( 'I', $event_end ) );
			\update_post_meta( $event_id, '_end_ampm', date( 'a', $event_end ) );
			\update_post_meta( $event_id, 'evcal_erow', $event_end );
		}

		if ( !empty( $data[ 'tickets_url' ] ) ) {
			\update_post_meta( $event_id, 'evcal_lmlink', $data[ 'tickets_url' ] );
		}
		
		if ( !empty( $data[ 'venue' ] ) ) {
			\wp_set_object_terms( $event_id, $data[ 'venue' ][ 'title' ], 'event_location', false  );
		}
		
		$tickets_status = 'scheduled';
		if ( !empty( $data[ 'status' ] ) and 'cancelled' == $data[ 'status' ] ) {
			$tickets_status = 'cancelled';
		}
		\update_post_meta( $event_id, '_status', $tickets_status );
		
		\update_post_meta( $event_id, 'evcal_subtitle', $this->get_rendered_template( 
			'subtitle', 
			$data, 
			$subscription 
		) );
						
		return $event_id;

	}
	
}
