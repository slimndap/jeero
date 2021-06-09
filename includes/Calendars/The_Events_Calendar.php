<?php
namespace Jeero\Calendars;

use Jeero\Helpers\Images as Images;

// Register new calendar.
register_calendar( __NAMESPACE__.'\\The_Events_Calendar' );

/**
 * The_Events_Calendar class.
 * 
 * @extends Calendar
 */
class The_Events_Calendar extends Calendar {

	function __construct() {
		
		$this->slug = 'The_Events_Calendar';
		$this->name = __( 'The Events Calendar', 'jeero' );
		
		parent::__construct();
		
	}
	
	function get_event_by_ref( $ref, $theater ) {
		
		error_log( sprintf( '[%s] Looking for existing %s item %s.', $this->get( 'name' ), $theater, $ref ) );
		
		$args = array(
			'post_status' => 'any',
			'meta_query' => array(
				array(
					'key' => $this->get_ref_key( $theater ),
					'value' => $ref,					
				),
			),
		);
		
		$events = \tribe_get_events( $args );
		
		if ( empty( $events ) ) {
			return false;
		}
		
		return $events[ 0 ]->ID;
		
	}
	
	/**
	 * Gets all fields for this calendar.
	 * 
	 * @since	1.8
	 * @since	1.10		Added the $subscription param.
	 *					Added support for custom fields.
	 * @return	array
	 */
	function get_fields( $subscription ) {
		
		$fields = parent::get_fields( $subscription );
		
		$fields = array_merge( $fields, $this->get_import_status_fields() );
		$fields = array_merge( $fields, $this->get_import_update_fields() );
		$fields = array_merge( $fields, $this->get_custom_fields_fields( $subscription ) );
		
		return $fields;
		
	}

	function get_venue_id( $title ) {
		$venue_id = wp_cache_get( $title, 'jeero/venue_id' );

		if ( false === $venue_id ) {
		
			$venue_post = get_page_by_title( $title, OBJECT, 'tribe_venue' );
			
			if ( !( $venue_post ) ) {
				$venue_id = tribe_create_venue( 
					array( 
						'Venue' => $title,
					)
				);
			} else {
				$venue_id = $venue_post->ID;
			}
			
			wp_cache_set( $title, $venue_id, 'jeero/venue_id' );
			
		}
		
		return $venue_id;		
	}
	
	/**
	 * Checks if this calendar is active.
	 * 
	 * @since	1.15
	 * @return	bool
	 */
	function is_active() {
		return class_exists( '\Tribe__Events__Main' );
	}
	
	/**
	 * Processes event data from Inbox items.
	 * 
	 * @since	1.0
	 * @since	1.4		Added the subscription param.
	 * @since	1.8		Added support for import settings to decide whether to 
	 * 					overwrite title/description/image/categorie during import.
	 * 					Added support for post status settings during import.
	 *					Added support for categories.
	 *					Added support for descriptions.
	 * @since	1.10		Added support for title and content Twig templates.
	 * @since	1.14		Added support for custom fields.	
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
		
		$ref = $data[ 'ref' ];
		
		$event_start = $this->localize_timestamp( strtotime( $data[ 'start' ] ) );

		$args = array(
			'EventStartDate' => date( 'Y-m-d', $event_start ),
			'EventStartHour' => date( 'H', $event_start ),
			'EventStartMinute' => date( 'i', $event_start ),
		);
		
		if ( !empty( $data[ 'end' ] ) ) {
			$event_end = $this->localize_timestamp( strtotime( $data[ 'end' ] ) );
		} else {
			$event_end = $event_start;
		}

		$args = array_merge( 
			$args, 
			array(
				'EventEndDate' => date( 'Y-m-d', $event_end ),
				'EventEndHour' => date( 'H', $event_end ),
				'EventEndMinute' => date( 'i', $event_end ),
			) 
		);

		if ( !empty( $data[ 'venue' ] ) ) {
			$args[ 'venue' ] = array(
				'VenueID' => $this->get_venue_id( $data[ 'venue' ][ 'title' ] ),
			);
		}
		
		if ( !empty( $data[ 'prices' ] ) ) {
			$amounts = \wp_list_pluck( $data[ 'prices' ], 'amount' );
			$args[ 'EventCost' ]	 = min( $amounts );
		}
		
		if ( !empty( $data[ 'tickets_url' ] ) ) {
			$args[ 'EventURL' ] = $data[ 'tickets_url' ];			
		}
		
		if ( $event_id = $this->get_event_by_ref( $ref, $theater ) ) {
			
			if ( 'always' == $this->get_setting( 'import/update/title', $subscription, 'once' ) ) {
				$args[ 'post_title' ] = $this->get_title_value( $data, $subscription );
			}
			
			if ( 'always' == $this->get_setting( 'import/update/description', $subscription, 'once' ) ) {
				$args[ 'post_content' ] = $this->get_content_value( $data, $subscription );
			}
						
			$event_id = \tribe_update_event( $event_id, $args );

			if ( 
				'always' == $this->get_setting( 'import/update/image', $subscription, 'once' ) && 
				!empty( $data[ 'production' ][ 'img' ] )
			) {
				$thumbnail_id = Images\update_featured_image_from_url( 
					$event_id,
					$data[ 'production' ][ 'img' ]
				);
			}

			if ( 'always' == $this->get_setting( 'import/update/categories', $subscription, 'once' ) ) {
				if ( empty( $data[ 'production' ][ 'categories' ] ) ) {
					\wp_set_object_terms( $event_id, array(), 'tribe_events_cat', false  );			
				} else {
					\wp_set_object_terms( $event_id, $data[ 'production' ][ 'categories' ], 'tribe_events_cat', false  );
				}
			}

			error_log( sprintf( '[%s] Updating %s event %s / %d.', $this->name, $theater, $ref, $event_id ) );
			
		} else {
			
			error_log( sprintf( '[%s] Creating %s event %s.', $this->name, $theater, $ref ) );

			$args[ 'post_title' ]= $this->get_title_value( $data, $subscription );
			$args[ 'post_content' ]= $this->get_content_value( $data, $subscription );
			$args[ 'post_status' ] = $this->get_setting( 'import/status', $subscription, 'draft' );
			
			$event_id = \tribe_create_event( $args );
			
			\add_post_meta( $event_id, $this->get_ref_key( $theater ), $data[ 'ref' ] );

			if ( !empty( $data[ 'production' ][ 'img' ] ) ) {
				$thumbnail_id = Images\update_featured_image_from_url( 
					$event_id,
					$data[ 'production' ][ 'img' ]
				);
			}

			if ( !empty( $data[ 'production' ][ 'categories' ] ) ) {
				\wp_set_object_terms( $event_id, $data[ 'production' ][ 'categories' ], 'tribe_events_cat', false  );
			}
			
		}

		$this->update_custom_fields( $event_id, $data, $subscription );
				
		return $event_id;
		
	}
	
}