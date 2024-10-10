<?php
namespace Jeero\Calendars;

// Register new calendar.
register_calendar( __NAMESPACE__.'\\Modern_Events_Calendar' );

/**
 * Modern_Events_Calendar class.
 *
 * @since	1.0.3
 * 
 * @extends Calendar
 */
class Modern_Events_Calendar extends Post_Based_Calendar {

	function __construct() {
		
		$this->slug = 'Modern_Events_Calendar';
		$this->name = __( 'Modern Events Calendar', 'jeero' );
		$this->categories_taxonomy = 'mec_category';

		parent::__construct();
		
	}
	
	function get_mec_instance( $lib ) {
		return \MEC::getInstance( sprintf( 'app.libraries.%s', $lib ) );		
	}
	
	function get_post_fields() {
		
		$post_fields = parent::get_post_fields();
		
		$new_post_fields = array();
		
		foreach( $post_fields as $post_field ) {
			
			if ( 'excerpt' == $post_field[ 'name' ] ) {
				continue;
			}
			$new_post_fields[] = $post_field;
		}
		
		return $new_post_fields;
		
	}

	function get_post_type() {
		return $this->get_mec_instance( 'main' )->get_main_post_type();
	}
	
	/**
	 * Checks if this calendar is active.
	 * 
	 * @since	1.15
	 * @since	1.15.2	Fix: Modern Events Calendar was not being detected any more.
	 * @return	bool
	 */
	function is_active() {
		return defined( 'MECEXEC' );
	}
	
	/**
	 * Gets the MEC location ID for a venue.
	 * 
	 * @since	1.11
	 * @since	1.17.5	Fixed a PHP error caused by incorrect syntax in $cache_group format specifier.
	 *					@see: https://github.com/slimndap/jeero/issues/9
	 * @param	array	$venue
	 * @return	int
	 */
	function get_location_id( $venue ) {
		
		$cache_group = sprintf( 'jeero/%s/location_id', $this->slug );

		$location_id = wp_cache_get( $venue[ 'title' ], $cache_group );

		if ( false === $location_id ) {
		
			$args = array(
				'name' => $venue[ 'title' ],
			);
			
			if ( !empty( $venue[ 'city' ] ) ) {
				$args[ 'address' ] = $venue[ 'city' ];
			}
		
			$location_id = $this->get_mec_instance( 'main' )->save_location( $args );
			
			wp_cache_set( $venue[ 'title' ], $location_id, $cache_group );
			
		}
		
		return $location_id;		
	}
	
	/**
	 * Processes event data from Inbox items.
	 * 
	 * @since	1.?
	 * @since	1.4		Added the subscription param.
	 * @since	1.9		Added support for import settings to decide whether to 
	 * 					overwrite title/description/image/category during import.
	 * 					Added support for post status settings during import.
	 *					Added support for categories.
	 * @since	1.11		Added support for title and content Twig templates.
	 *					More info link now get a 'Tickets' label.
	 *					Added support for prices.
	 *					Added suport for locations.
	 *					Added suuport for cancelled events.
	 *					Fixed import of categories.
	 * @since	1.23.1	Now uses local number format for event prices.
	 * @since	1.29.1	No longer uses local number format for event prices, because the MEC input field for
	 *					prices only accepts '.' as separator.
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
		
		// Temporarily disable new event notifications.
		remove_action( 'mec_event_published', array( $this->get_mec_instance( 'notifications' ), 'user_event_publishing'), 10 );

		$ref = $this->get_post_ref( $data );

		if ( $post_id = $this->get_event_by_ref( $ref, $theater ) ) {

			$post = \get_post( $post_id );

			$event_start = strtotime( $data[ 'start' ] );

			$args = array (
				'title' => $post->post_title,
				'content' => $post->post_content,
				'status' => $post->post_status,
			    'start'=> date( 'Y-m-d', $event_start ),
			    'start_time_hour' => date( 'g', $event_start ),
			    'start_time_minutes'=> date( 'i', $event_start ),
			    'start_time_ampm' => date( 'A', $event_start ),
			    'interval' => NULL,
			    'repeat_type' => '',
			    'repeat_status' => 0,
			    'meta' => array (
			        'mec_source' => $theater,
	                'mec_more_info'=> $data[ 'tickets_url' ],
	                'mec_more_info_title' => __( 'Tickets', 'jeero' ),
	                'mec_more_info_target' => '_self',
			    )
			);
			
			if ( !empty( $data[ 'end' ] ) ) {
				$event_end = strtotime( $data[ 'end' ] );
			} else {
				$event_end = $event_start;			
				$args[ 'date' ] = array(
					'hide_end_time' => 1,
				);
			}

			$args = array_merge( $args, array(
			    'end' => date( 'Y-m-d', $event_end ),
			    'end_time_hour' => date( 'g', $event_end ),
			    'end_time_minutes' => date( 'i', $event_end ),
			    'end_time_ampm' => date( 'A', $event_end ),				
			) );
				
			if ( !empty( $data[ 'prices' ] ) ) {
				$amounts = \wp_list_pluck( $data[ 'prices' ], 'amount' );
				$args[ 'meta' ][ 'mec_cost' ] = number_format( min( $amounts ), 2 );
			}

			$event_status = 'EventScheduled';
			if ( !empty( $data[ 'status' ] ) ) {
				switch( $data[ 'status' ] ) {
					case 'cancelled':
						$event_status = 'EventCancelled';
						break;
					default:
						$event_status = 'EventScheduled';
				}
			}
			$args[ 'meta' ][ 'mec_event_status' ] = $event_status;
		
			if ( !empty( $data[ 'venue' ] ) ) {
				$args[ 'meta' ][ 'mec_location_id' ] = $this->get_location_id( $data[ 'venue' ] );
			}

			// Temporarily disable sanitizing allowed HTML tags.
			\remove_filter( 'content_save_pre', 'wp_filter_post_kses' );

			$this->get_mec_instance( 'main' )->save_event( $args, $post_id );        	

			// Re-enable sanitizing allowed HTML tags.
			\add_filter( 'content_save_pre', 'wp_filter_post_kses' );

		}
		
		// Re-enable new event notifications.
		add_action( 'mec_event_published', array( $this->get_mec_instance( 'notifications' ), 'user_event_publishing'), 10, 3 );

		return $post_id;
		
	}
	
}