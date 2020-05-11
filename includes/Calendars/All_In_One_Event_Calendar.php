<?php
namespace Jeero\Calendars;

const JEERO_CALENDARS_ALL_IN_ONE_EVENT_CALENDAR_REF_KEY = 'jeero/all_in_one_event_calendar/ref';

/**
 * The_Events_Calendar class.
 * 
 * @extends Calendar
 */
class All_In_One_Event_Calendar extends Calendar {

	function __construct() {
		
		$this->slug = 'All_In_One_Event_Calendar';
		$this->name = __( 'All In One Event Calendar', 'jeero' );
		
		parent::__construct();
		
	}
	
	function get_event_by_ref( $ref ) {
		
		$args = array(
			'post_type' => \AI1EC_POST_TYPE,
			'post_status' => 'any',
			'meta_query' => array(
				array(
					'key' => JEERO_CALENDARS_ALL_IN_ONE_EVENT_CALENDAR_REF_KEY,
					'value' => $ref,					
				),
			),
		);
		
		$events = get_posts( $args );
		
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
	
	function process_data( $data, $raw, $theater ) {
		
		global $ai1ec_front_controller;
		
		$data = parent::process_data( $data, $raw, $theater );
		
		if ( \is_wp_error( $data ) ) {			
			return $data;
		}
		
		$ref = $data[ 'ref' ];

        $args = array(
            'ticket_url' => $data[ 'tickets_url' ],
            'post' => array(
				'post_status' => 'draft',
				'post_type' => \AI1EC_POST_TYPE,
				'post_title' => $data[ 'production' ][ 'title' ],
				'post_content' => '',
            ),
        );

		if ( !empty( $data[ 'venue' ] ) ) {
			$args[ 'venue' ] = $data[ 'venue' ][ 'title' ];
		}
		
		if ( !empty( $data[ 'prices' ] ) ) {
			$amounts = \wp_list_pluck( $data[ 'prices' ], 'amount' );
			$args[ 'cost' ]	 = min( $amounts );
		}
		
		$Ai1ec_Event = $ai1ec_front_controller->return_registry( true )->get( 'model.event', $args );		

		if ( $event_id = $this->get_event_by_ref( $ref ) ) {
						
			$Ai1ec_Event->set( 'post_id', $event_id );
			$Ai1ec_Event->set( 'post', get_post( $event_id ) );
			$Ai1ec_Event->save( true );

			error_log( sprintf( '[%s] Updating event %d / %d.', $this->name, $ref, $event_id ) );
			
			
		} else {
			
			error_log( sprintf( '[%s] Creating event %d.', $this->name, $ref ) );
			
			$event_id = $Ai1ec_Event->save();
			
			if ( !$event_id ) {
				return new \WP_Error( $this->slug, sprintf( 'could not save event %d.', $ref ) );
			}
			
			\add_post_meta( $event_id, JEERO_CALENDARS_ALL_IN_ONE_EVENT_CALENDAR_REF_KEY, $data[ 'ref' ] );
		}

		
		return $data;
		
	}
	
}