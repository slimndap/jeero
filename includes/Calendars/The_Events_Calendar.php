<?php
namespace Jeero\Calendars;

// Register new calendar.
register_calendar( __NAMESPACE__.'\\The_Events_Calendar' );

/**
 * The_Events_Calendar class.
 * 
 * @extends Calendar
 */
class The_Events_Calendar extends Post_Based_Calendar {

	function __construct() {
		
		$this->slug = 'The_Events_Calendar';
		$this->name = __( 'The Events Calendar', 'jeero' );
		$this->post_type = 'tribe_events';
		$this->categories_taxonomy = 'tribe_events_cat';
		
		parent::__construct();
		
	}
	
	function get_event_by_ref( $ref, $theater ) {
		
		error_log( sprintf( '[%s] Looking for existing %s item %s.', $this->get( 'name' ), $theater, $ref ) );
		
		$args = array(
			'status' => array( 'any' ),
			'meta_query' => array(
				array(
					'key' => $this->get_ref_key( $theater ),
					'value' => $ref,					
				),
			),
			'cache_buster' => wp_generate_uuid4( ),
		);

		$posts = \tribe_get_events( $args );

		if ( empty( $posts ) ) {
			return false;
		}
		
		return $posts[ 0 ]->ID;
		
	}
	
	/**
	 * Gets all post fields for this calendar.
	 * 
	 * @since	1.17
	 * @return	array
	 */
	function get_post_fields() {
		
		$post_fields = parent::get_post_fields();
		
		$post_fields[] = array(
			'name' => 'venue_Title',
			'title' => __( 'Location title', 'jeero' ),
			'template' => '{{ venue.title }}',
		);

		$post_fields[] = array(
			'name' => 'venue_Address',
			'title' => __( 'Location address', 'jeero' ),
			'template' => '',
		);

		$post_fields[] = array(
			'name' => 'venue_City',
			'title' => __( 'Location city', 'jeero' ),
			'template' => '{{ venue.city }}',
		);

		$post_fields[] = array(
			'name' => 'venue_Zip',
			'title' => __( 'Location postal code', 'jeero' ),
			'template' => '',
		);

		$post_fields[] = array(
			'name' => 'venue_Phone',
			'title' => __( 'Location phone', 'jeero' ),
			'template' => '',
		);

		$post_fields[] = array(
			'name' => 'venue_Website',
			'title' => __( 'Location website', 'jeero' ),
			'template' => '',
		);

		return $post_fields;		
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
	 * @since	1.15.3	Fix: start and end times were incorreclty localized, resulting in
	 *					the start and end times being off.
	 * @since	1.17		Added support for custom venue title template.
	 *					Added support for venue meta fields.
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
		
		$ref = $this->get_post_ref( $data );
		
		if ( $post_id = $this->get_event_by_ref( $ref, $theater ) ) {

			$event_start = strtotime( $data[ 'start' ] );
				
			$args = array(
				'EventStartDate' => date( 'Y-m-d', $event_start ),
				'EventStartHour' => date( 'H', $event_start ),
				'EventStartMinute' => date( 'i', $event_start ),
			);
			
			if ( !empty( $data[ 'end' ] ) ) {
				$event_end = strtotime( $data[ 'end' ] );
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

			$venue_title = $this->get_rendered_template( 'venue_Title', $data, $subscription );
			
			if ( !empty( $venue_title ) ) {
				
				$venue_id = $this->get_post_id_by_title( $venue_title, 	'tribe_venue' );
				
				$venue = \tribe_get_venue_object( $venue_id );
				$venue_args = array();

				foreach ( array( 'Address', 'City', 'Zip', 'Phone', 'Website' ) as $meta_field ) {

					$post_field = 'venue_'.$meta_field;
					
					if ( empty( $venue->{ $meta_field } ) ) {
						$venue_args[ $meta_field ] = $this->get_rendered_template( $post_field, $data, $subscription );
						continue;
					}
					
					if ( 
						!empty( $post_fields[ $post_field ] ) &&
						!empty( $post_fields[ $post_field ][ 'update' ] ) &&
						'always' == $post_fields[ $post_field ][ 'update' ]
					) {
						$venue_args[ $meta_field ] = $this->get_rendered_template( $post_field, $data, $subscription );
					}
					
				}
				if ( !empty( $venue_args )) {
					tribe_update_venue( $venue_id, $venue_args );
				}
				
				$args[ 'venue' ] = array( 
					'VenueID' => $venue_id,
				);
				
			}
		
			if ( !empty( $data[ 'prices' ] ) ) {
				$amounts = \wp_list_pluck( $data[ 'prices' ], 'amount' );
				$args[ 'EventCost' ]	 = min( $amounts );
			}
			
			if ( !empty( $data[ 'tickets_url' ] ) ) {
				$args[ 'EventURL' ] = $data[ 'tickets_url' ];			
			}
			
			// Temporarily disable sanitizing allowed HTML tags.
			\remove_filter( 'content_save_pre', 'wp_filter_post_kses' );
	
			$post_id = \tribe_update_event( $post_id, $args );

			// Re-enable sanitizing allowed HTML tags.
			\add_filter( 'content_save_pre', 'wp_filter_post_kses' );

			
		}

		return $post_id;
		
	}
	
}