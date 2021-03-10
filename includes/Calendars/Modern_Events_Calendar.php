<?php
namespace Jeero\Calendars;

use Jeero\Helpers\Images as Images;

/**
 * Modern_Events_Calendar class.
 *
 * @since	1.0.3
 * 
 * @extends Calendar
 */
class Modern_Events_Calendar extends Calendar {

	function __construct() {
		
		$this->slug = 'Modern_Events_Calendar';
		$this->name = __( 'Modern Events Calendar', 'jeero' );
		
		parent::__construct();
		
	}
	
	function get_event_by_ref( $ref, $theater ) {
		
		error_log( sprintf( '[%s] Looking for existing %s item %s.', $this->get( 'name' ), $theater, $ref ) );
		
		$args = array(
			'post_type' => $this->get_mec_instance( 'main' )->get_main_post_type(),
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
	
	function get_mec_instance( $lib ) {
		return \MEC::getInstance( sprintf( 'app.libraries.%s', $lib ) );		
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
		
		$ref = $data[ 'ref' ];

		$event_start = $this->localize_timestamp( strtotime( $data[ 'start' ] ) );
		$event_end = $this->localize_timestamp( strtotime( $data[ 'end' ] ) );

		$args = array (
		    'title' => $data[ 'production' ][ 'title' ],
		    'content' => $data[ 'production' ][ 'description' ],
		    //'location_id'=>$location_id,
		    'start'=> date( 'Y-m-d', $event_start ),
		    'start_time_hour' => date( 'g', $event_start ),
		    'start_time_minutes'=> date( 'i', $event_start ),
		    'start_time_ampm' => date( 'A', $event_start ),
		    'end' => date( 'Y-m-d', $event_end ),
		    'end_time_hour' => date( 'g', $event_end ),
		    'end_time_minutes' => date( 'i', $event_end ),
		    'end_time_ampm' => date( 'A', $event_end ),
		    'interval' => NULL,
		    'repeat_type' => '',
		    'repeat_status' => 0,
		    'meta'=>array
		    (
		        'mec_source' => $theater,
                'mec_more_info'=> $data[ 'tickets_url' ],
		    )
		);

		// Temporarily disable new event notifications.
		remove_action( 'mec_event_published', array( $this->get_mec_instance( 'notifications' ), 'user_event_publishing'), 10 );

		if ( $event_id = $this->get_event_by_ref( $ref, $theater ) ) {
			error_log( sprintf( '[%s] Updating event %d / %d.', $this->name, $ref, $event_id ) );

			$this->get_mec_instance( 'main' )->save_event( $args, $event_id );        	

		} else {
			error_log( sprintf( '[%s] Creating event %d.', $this->name, $ref ) );

			$event_id = $this->get_mec_instance( 'main' )->save_event( $args );        				

			\add_post_meta( $event_id, $this->get_ref_key( $theater ), $data[ 'ref' ] );
		}		

		// Re-enable new event notifications.
		add_action( 'mec_event_published', array( $this->get_mec_instance( 'notifications' ), 'user_event_publishing'), 10, 3 );

		$thumbnail_id = Images\update_featured_image_from_url( 
			$event_id,
			$data[ 'production' ][ 'img' ]
		);
		
		return $event_id;
		
	}
	
}