<?php
namespace Jeero\Calendars;

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
	
	function process_data( $result, $data, $raw, $theater ) {
		
		$result = parent::process_data( $result, $data, $raw, $theater );
		
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
		
		if ( $event_id = $this->get_event_by_ref( $ref, $theater ) ) {
			
			$event_id = \tribe_update_event( $event_id, $args );

			error_log( sprintf( '[%s] Updating event %d / %d.', $this->name, $ref, $event_id ) );
			
		} else {
			
			error_log( sprintf( '[%s] Creating event %d.', $this->name, $ref ) );

			$args[ 'post_title' ]= $data[ 'production' ][ 'title' ];
			$args[ 'EventURL' ] = $data[ 'tickets_url' ];
			
			$event_id = \tribe_create_event( $args );
			
			\add_post_meta( $event_id, $this->get_ref_key( $theater ), $data[ 'ref' ] );

		}
		
		return $event_id;
		
	}
	
}