<?php
namespace Jeero\Calendars;

use Jeero\Helpers\Images as Images;

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
class Very_Simple_Event_List extends Calendar {

	function __construct() {
		
		$this->slug = 'Very_Simple_Event_List';
		$this->name = __( 'Very Simple Event List', 'jeero' );
		
		parent::__construct();
		
	}
	
	function get_event_by_ref( $ref, $theater ) {
		
		error_log( sprintf( '[%s] Looking for existing %s item %s.', $this->get( 'name' ), $theater, $ref ) );
		
		$args = array(
			'post_type' => 'event',
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
	
	function is_active() {
		return is_plugin_active( 'very-simple-event-list/vsel.php' );
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

		$post_content = '';
		if ( !empty( $data[ 'production' ][ 'description' ] ) ) {
			$post_content = $data[ 'production' ][ 'description' ];
		}
		
		$args = array(
			'post_type' => 'event',
			'post_title' => $data[ 'production' ][ 'title' ],
			'post_content' => $post_content,
		);

		if ( $event_id = $this->get_event_by_ref( $ref, $theater ) ) {
			error_log( sprintf( '[%s] Updating event %s / %d.', $this->name, $ref, $event_id ) );
			
			$args[ 'ID' ] = $event_id;

			wp_update_post( $args );
			
		} else {
			error_log( sprintf( '[%s] Creating event %s.', $this->name, $ref ) );

			$args[ 'post_status' ] = 'draft';

			$event_id = wp_insert_post( $args );

			\add_post_meta( $event_id, $this->get_ref_key( $theater ), $data[ 'ref' ] );

		}
		
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
		
		if ( !empty( $data[ 'production' ][ 'img' ] ) ) {
			
			$thumbnail_id = Images\add_image_to_library( 
				$data[ 'production' ][ 'img' ],
				$event_id
			);
			
			if ( \is_wp_error( $thumbnail_id ) ) {
				
			} else {
				\update_post_meta( $event_id, '_thumbnail_id', $thumbnail_id );					
			}

		}

		return $event_id;
		
		
	}
	
}