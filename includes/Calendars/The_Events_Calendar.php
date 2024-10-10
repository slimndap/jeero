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
	
	/**
	 * Gets an event post by its ref and theater values.
	 * 
	 * @since	1.?
	 * @since	1.18		@uses \Jeero\Calendars\Calendar::log().
	 * @since	1.20.2	Replaced tribe_get_events() with get_posts().
	 *					tribe_get_events() sometimes does not return events that were created with
	 *					tribe_create_event():
	 *					@see: https://wordpress.org/support/topic/tribe_create_event-unable-to-save-startdate/
	 *
	 * @param 	string			$ref
	 * @param 	string 			$theater
	 * @return 	WP_POST|bool					The event post or <false> if not found.
	 */
	function get_event_by_ref( $ref, $theater ) {

		$this->log( sprintf( 'Looking for existing %s item %s.', $theater, $ref ) );
		
		$args = array(
			// Trick TEC to not alter the get_posts() query by adding a second post_type to the query.
			'post_type' => array( $this->get_post_type(), 'fake_post_type' ),
			'post_status' => 'any',
			'meta_query' => array(
				array(
					'key' => $this->get_ref_key( $theater ),
					'value' => $ref,					
				),
			),
		);

		$posts = \get_posts( $args );

		if ( empty( $posts ) ) {
			return false;
		}
		
		return $posts[ 0 ]->ID;
	}
	
	/**
	 * Gets a serie post by its ref and theater values.
	 * 
	 * @since	1.22
	 * @since	1.25.1	Fixed error in $args that made the value of 'post_status' obsolete, 
	 * 					making it impossible to find series with a post status of 'draft'.
	 *
	 * @param 	string			$ref
	 * @param 	string 			$theater
	 * @return 	WP_POST|bool					The serie post or <false> if not found.
	 */
	function get_serie_by_ref( $ref, $theater ) {

		$this->log( sprintf( 'Looking for existing %s serie %s.', $theater, $ref ) );
		
		$args = array(
			'post_status' => array( 'any' ),
			'post_type' => \TEC\Events_Pro\Custom_Tables\V1\Series\Post_Type::POSTTYPE,
			'meta_query' => array(
				array(
					'key' => $this->get_ref_key( $theater ),
					'value' => $ref,					
				),
			),
		);

		$posts = \get_posts( $args );

		if ( empty( $posts ) ) {
			return false;
		}
		
		return $posts[ 0 ]->ID;
		
	}
	
	/**
	 * Gets all setting fields for a subscription.
	 * 
	 * @since	1.22
	 * @param 	Jeero\Subscription	$subscription
	 * @return	array
	 */
	function get_setting_fields( $subscription ) {
		
		$fields = parent::get_setting_fields( $subscription );
		
		if ( !$this->can_use_series() ) {
			return $fields;
		}

		$filtered_fields = array();

		foreach( $fields as $field ) {

			$filtered_fields[] = $field;

			if ( 'calendar' == $field[ 'name' ] ) {
				$filtered_fields[] = array(
					'name' => sprintf( '%s/import/use_series', $this->slug ),
					'label' => __( 'Series', 'jeero' ),
					'type' => 'checkbox',
					'choices' => array(
						'1' => __( 'Use series to group events with a common parent', 'jeero' ),
					),
					'instructions' => __( 'Eg. events for the same movie or events that are part of a festival.', 'jeero' ),
				);
			}
		}

		return $filtered_fields;
		
	}
	
	/**
	 * Gets all post fields for this calendar.
	 * 
	 * @since	1.17
	 * @since	1.22		Added serie content field.
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
			'name' => 'venue_URL',
			'title' => __( 'Location website', 'jeero' ),
			'template' => '',
		);
		
		if ( $this->can_use_series() ) {

			$post_fields[] = array(
				'name' => 'serie_content',
				'title' => __( 'Serie content', 'jeero' ),
				'template' => '{{ description|raw }}',
			);		
			
		}

		return $post_fields;		
	}

	/**
	 * Inserts or updates a post without sanitizing post content for allowed HTML tags.
	 * Uses tribe_create_event() to ensure that all necessary database updates are done by 
	 * The Events Calendar.
	 * 
	 * @since	1.20.2
	 * @param 	array 			$args	An array of elements that make up a post to update or insert.
	 * @return	int|WP_Error				The post ID on success. The value 0 or WP_Error on failure.
	 */
	function insert_post( $args ) {
		
		// Temporarily disable sanitizing allowed HTML tags.
		\remove_filter( 'content_save_pre', 'wp_filter_post_kses' );
		
		// Use a dummy date to make sure that $args meets the minimum requirements of tribe_create_event().
		$defaults = array(
			'EventStartDate' => date( 'Y-m-d' ),
			'EventEndDate' => date( 'Y-m-d' ),
			'EventStartHour' => 19,
			'EventStartMinute' => 15,
			'EventEndHour' => 20,
			'EventEndMinute' => 15,
		);
		
		$args = wp_parse_args( $args, $defaults );
		
		$result = \tribe_create_event( $args );

		// Re-enable sanitizing allowed HTML tags.
		\add_filter( 'content_save_pre', 'wp_filter_post_kses' );
		
		return $result;
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
	 * 					overwrite title/description/image/categories during import.
	 * 					Added support for post status settings during import.
	 *					Added support for categories.
	 *					Added support for descriptions.
	 * @since	1.10		Added support for title and content Twig templates.
	 * @since	1.14		Added support for custom fields.	
	 * @since	1.15.3	Fix: start and end times were incorrectly localized, resulting in
	 *					the start and end times being off.
	 * @since	1.17		Added support for custom venue title template.
	 *					Added support for venue meta fields.
	 * @since	1.17.1	Now uses local number format for event prices.
	 *					Now remembers map settings.
	 * @since	1.22		Added support for series.
	 *					Fix: venue field did not obey the update settings. Fixes #16.
	 * @since	1.23		Added support for event statuses.
	 * @since	1.29		Use default TEC end time if no end time is available.
	 * @since	1.29.2	Use start time if no end time is available, except if end time was previously entered manually in TEC.
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
			
			if ( empty( $data[ 'end' ] ) ) {

				/** 
				 * No end time provided. 
				 * Set end time to start time, except if end time was previously entered manually.
				 */

				$existing_end_date = get_post_meta( $post_id, '_EventEndDate', true );

				if ( empty( $existing_end_date ) ) {
					$event_end = $event_start;
				} else {
					$event_end = strtotime( $existing_end_date );
				}
				
			} else {
				$event_end = strtotime( $data[ 'end' ] );				
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
				
				$venue_id = \tribe_get_venue_id( $post_id );

				$post_fields = $this->get_setting( 'import/post_fields', $subscription );

				if ( 
					empty( $venue_id ) ||
					(
						!empty( $post_fields[ 'venue_Title' ] ) &&
						!empty( $post_fields[ 'venue_Title' ][ 'update' ] ) &&
						'always' == $post_fields[ 'venue_Title' ][ 'update' ]
					)
				) {
					$venue_id = $this->get_post_id_by_title( $venue_title, 'tribe_venue' );
				}

				$venue = \tribe_get_venue_object( $venue_id );
				$venue_args = array();

				foreach ( array( 'Address', 'City', 'Zip', 'Phone', 'URL' ) as $meta_field ) {

					$post_field = 'venue_'.$meta_field;

					if ( empty( $venue->{ sanitize_title( $meta_field ) } ) ) {
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
					// Copy map settings that may have been manually entered previously through the admin interface,
					'EventShowMap' => tribe_embed_google_map( $post_id ),
					'EventShowMapLink' => get_post_meta( $post_id, '_EventShowMapLink', true ),
				);
				
			}
		
			if ( !empty( $data[ 'prices' ] ) ) {
				$amounts = \wp_list_pluck( $data[ 'prices' ], 'amount' );
				$args[ 'EventCost' ] = number_format_i18n( min( $amounts ), 2 );
			}
			
			if ( !empty( $data[ 'tickets_url' ] ) ) {
				$args[ 'EventURL' ] = $data[ 'tickets_url' ];			
			}
			

			
			// Temporarily disable sanitizing allowed HTML tags.
			\remove_filter( 'content_save_pre', 'wp_filter_post_kses' );
	
			$post_id = \tribe_update_event( $post_id, $args );

			// Re-enable sanitizing allowed HTML tags.
			\add_filter( 'content_save_pre', 'wp_filter_post_kses' );

			// Add event to serie
			if ( $this->use_series( $subscription ) ) {
				
				if ( !empty( $data[ 'production' ][ 'ref' ] ) ) {

					$event = \tribe_get_event( $post_id );
					
					$serie_ref = $data[ 'production' ][ 'ref' ];
					$serie_id = $this->get_serie_by_ref( $serie_ref, $theater);
					
					if ( !$serie_id ) {
						
						$serie_args = array(
							'post_type' => \TEC\Events_Pro\Custom_Tables\V1\Series\Post_Type::POSTTYPE,
							'post_status' => $this->get_setting( 'import/status', $subscription, 'draft' ),
							'post_title'  => $data[ 'production' ][ 'title' ],
							'post_content' => $this->get_rendered_template( 'serie_content', $data, $subscription ),
						);
						
						$serie_id = \wp_insert_post( $serie_args );
						
					}

					$serie = \get_post( $serie_id );

					\tribe_update_event_with_series( $event, $serie );					
					
					\update_post_meta( $serie->ID, $this->get_ref_key( $theater ), $serie_ref );
					
				}
								
			}

			if ( isset( $data[ 'status' ] ) ) {

				switch( $data[ 'status' ] ) {
					case 'cancelled':
						$status = 'canceled';
						break;
					default:
						$status = '';
				}

				update_post_meta( $post_id, \Tribe\Events\Event_Status\Event_Meta::$key_status, $status );
				
			}
			
						
		}

		return $post_id;
		
	}
	
	function can_use_series() {		

		return function_exists( '\tribe_update_event_with_series' );
		
	}
	
	function use_series( $subscription ) {
		
		if ( !$this->can_use_series() ) {
			return false;
		}
		
		$use_series = $this->get_setting( 'import/use_series', $subscription, false );
		
		return !empty( $use_series );
		
	}

}