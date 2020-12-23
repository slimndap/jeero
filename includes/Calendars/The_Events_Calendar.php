<?php
namespace Jeero\Calendars;

use Jeero\Helpers\Images as Images;

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
	 * @return	int|WP_Error
	 */	 
	function process_data( $result, $data, $raw, $theater, $subscription ) {
		
		$result = parent::process_data( $result, $data, $raw, $theater, $subscription );
		
		if ( \is_wp_error( $result ) ) {			
			return $result;
		}
		
		$ref = $data[ 'ref' ];
		
		$event_start = $this->localize_timestamp( strtotime( $data[ 'start' ] ) );
		$event_end = $this->localize_timestamp( strtotime( $data[ 'end' ] ) );

		$args = array(
			'EventStartDate' => date( 'Y-m-d', $event_start ),
			'EventStartHour' => date( 'H', $event_start ),
			'EventStartMinute' => date( 'i', $event_start ),
			'EventEndDate' => date( 'Y-m-d', $event_end ),
			'EventEndHour' => date( 'H', $event_end ),
			'EventEndMinute' => date( 'i', $event_end ),
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
			
			$event_id = \tribe_update_event( $event_id, $args );

			error_log( sprintf( '[%s] Updating event %d / %d.', $this->name, $ref, $event_id ) );
			
		} else {
			
			error_log( sprintf( '[%s] Creating event %d.', $this->name, $ref ) );

			$args[ 'post_title' ]= $data[ 'production' ][ 'title' ];
			
			$event_id = \tribe_create_event( $args );
			
			\add_post_meta( $event_id, $this->get_ref_key( $theater ), $data[ 'ref' ] );

		}

		if ( !empty( $data[ 'production' ][ 'img' ] ) ) {
			$thumbnail_id = Images\update_featured_image_from_url( 
				$event_id,
				$data[ 'production' ][ 'img' ]
			);
		}
		
		return $event_id;
		
	}
	
}