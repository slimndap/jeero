<?php
namespace Jeero\Calendars;

use Jeero\Helpers\Images as Images;

// Register new calendar.
register_calendar( __NAMESPACE__.'\\Events_Schedule_Wp_Plugin' );

/**
 * Events_Schedule_Wp_Plugin class.
 *
 * @since	1.0.3
 * 
 * @extends Calendar
 */
class Events_Schedule_Wp_Plugin extends Calendar {

	function __construct() {
		
		$this->slug = 'Events_Schedule_Wp_Plugin';
		$this->name = __( 'Events Schedule WP Plugin', 'jeero' );
		
		parent::__construct();
		
	}
	
	function get_event_by_ref( $ref, $theater ) {
		
		error_log( sprintf( '[%s] Looking for existing %s item %s.', $this->get( 'name' ), $theater, $ref ) );
		
		$args = array(
			'post_type' => 'class',
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
	 * @since	1.6
	 * @since	1.10		Added the $subscription param.
	 * @return	array
	 */
	function get_fields( $subscription ) {
		
		$fields = parent::get_fields( $subscription );
		
		$fields = array_merge( $fields, $this->get_import_status_fields( $subscription ) );
		
		$fields[] = array(
			'name' => sprintf( '%s/import/class_types', $this->slug ),
			'label' => __( 'Class Types', 'WeeklyClass' ),
			'type' => 'checkbox',
			'choices' => array(
				'class_types' => sprintf( __( 'Import categories as %s', 'jeero' ), __( 'Class Types', 'WeeklyClass' ) ),
			),
		);
		
		$fields = array_merge( $fields, $this->get_import_update_fields( $subscription ) );
		
		return $fields;
		
	}

	/**
	 * Checks if this calendar is active.
	 * 
	 * @since	1.15
	 * @return	bool
	 */
	function is_active() {
		return defined( 'WCS_FILE' );
	}
	
	/**
	 * Processes event data from Inbox items.
	 * 
	 * @since	1.?
	 * @since	1.4		Added the subscription param.
	 * @since	1.6		Added support for import settings to decide whether to 
	 * 					overwrite title/description/image during import.
	 * 					Added support for post status settings during import.
	 *					Added support for venues.
	 *					Added support for categories.
	 *
	 * @param 	mixed 			$result
	 * @param 	array			$data		The structured data of the event.
	 * @param 	array			$raw		The raw data of the event.
	 * @param	string			$theater		The theater.
	 * @param	Subscription		$theater		The subscription.
	 */
	function process_data( $result, $data, $raw, $theater, $subscription ) {
		
		$result = parent::process_data( $result, $data, $raw, $theater, $subscription );
		
		if ( \is_wp_error( $result ) ) {			
			return $result;
		}
		
		$ref = $data[ 'ref' ];

		$event_start = $this->localize_timestamp( strtotime( $data[ 'start' ] ) );

		$args = array(
			'post_type' => 'class',
		);

		if ( $event_id = $this->get_event_by_ref( $ref, $theater ) ) {
			error_log( sprintf( '[%s] Updating event %s / %d.', $this->name, $ref, $event_id ) );
			
			$args[ 'ID' ] = $event_id;
			if ( 'always' == $this->get_setting( 'import/update/title', $subscription, 'once' ) ) {
				$args[ 'post_title' ] = $data[ 'production' ][ 'title' ];
			}
			
			if ( 'always' == $this->get_setting( 'import/update/description', $subscription, 'once' ) ) {
				$args[ 'post_content' ] = $data[ 'production' ][ 'description' ] ?? '';
			}

			wp_update_post( $args );

			if ( 
				'always' == $this->get_setting( 'import/update/image', $subscription, 'once' ) && 
				!empty( $data[ 'production' ][ 'img' ] )
			) {
				$this->update_image( $event_id, $data[ 'production' ][ 'img' ] );
			}
						
			if ( 
				!empty( $this->get_setting( 'import/class_types', $subscription ) ) &&
				( in_array( 'class_types', $this->get_setting( 'import/class_types', $subscription ) ) )  &&
				( 'always' == $this->get_setting( 'import/update/categories', $subscription, 'once' ) )
			) {
				if ( empty( $data[ 'production' ][ 'categories' ] ) ) {
					\wp_set_object_terms( $event_id, array(), 'wcs-type', false  );			
				} else {
					\wp_set_object_terms( $event_id, $data[ 'production' ][ 'categories' ], 'wcs-type', false  );
				}
			}

		} else {
			error_log( sprintf( '[%s] Creating event %s.', $this->name, $ref ) );

			$args[ 'post_status' ] = $this->get_setting( 'import/status', $subscription, 'draft' );
			$args[ 'post_title' ] = $data[ 'production' ][ 'title' ];
			$args[ 'post_content' ] = $data[ 'production' ][ 'description' ] ?? '';

			$event_id = wp_insert_post( $args );

			\add_post_meta( $event_id, $this->get_ref_key( $theater ), $data[ 'ref' ] );

			if ( !empty( $data[ 'production' ][ 'img' ] ) ) {
				$this->update_image( $event_id, $data[ 'production' ][ 'img' ] );
			}

			if ( 
				!empty( $this->get_setting( 'import/class_types', $subscription ) ) &&
				in_array( 'class_types', $this->get_setting( 'import/class_types', $subscription ) ) 
			) {
				if ( !empty( $data[ 'production' ][ 'categories' ] ) ) {
					\wp_set_object_terms( $event_id, $data[ 'production' ][ 'categories' ], 'wcs-type', false  );
				}				
			}
			
		}
		
		\update_post_meta( $event_id, '_wcs_timestamp', $event_start );
		
		if ( empty( $data[ 'tickets_url' ] ) ) {
			\delete_post_meta( $event_id, '_wcs_action_label' );
			\delete_post_meta( $event_id, '_wcs_action_call' );
			\delete_post_meta( $event_id, '_wcs_action_custom' );
			\delete_post_meta( $event_id, '_wcs_interval' );
		} else {
			\update_post_meta( $event_id, '_wcs_action_label', __( 'Tickets', 'jeero' ) );
			\update_post_meta( $event_id, '_wcs_action_call', 1 );
			\update_post_meta( $event_id, '_wcs_action_custom', $data[ 'tickets_url' ] );
			\update_post_meta( $event_id, '_wcs_interval', 0 );			
		}

		if ( !empty( $data[ 'end' ] ) ) {
			$event_end = $this->localize_timestamp( strtotime( $data[ 'end' ] ) );	
			\update_post_meta( $event_id, '_wcs_duration', ( $event_end - $event_start ) / MINUTE_IN_SECONDS );
		}
		
		if ( !empty( $data[ 'production' ][ 'img' ] ) ) {
			
			$thumbnail_id = Images\add_image_to_library( 
				$data[ 'production' ][ 'img' ],
				$event_id
			);
			
			if ( \is_wp_error( $thumbnail_id ) ) {
				
			} else {
				if ( $image_src = wp_get_attachment_image_src( $thumbnail_id, 'full' ) ) {
					\update_post_meta( $event_id, '_wcs_image', $image_src[ 0 ] );					
				}
			}

		}
		
		if ( empty( $data[ 'venue' ] ) && !empty( $data[ 'venue' ][ 'title' ] ) ) {
			\wp_set_object_terms( $event_id, array(), 'wcs-room', false  );			
		} else {
			\wp_set_object_terms( $event_id, $data[ 'venue' ][ 'title' ], 'wcs-room', false  );
		}

		return $event_id;
		
	}
	
	/**
	 * Updates the image of a production.
	 * 
	 * @since	1.6
	 * @param 	int		$event_id
	 * @param	string 	$image_url
	 * @return 	void
	 */
	function update_image( $event_id, $image_url ) { 
		
		$thumbnail_id = Images\add_image_to_library( 
			$image_url,
			$event_id
		);
		
		if ( \is_wp_error( $thumbnail_id ) ) {
			error_log( sprintf( 'Updating thumbnail for event failed %s / %d.', get_the_title( $event_id ), $event_id ) );			
		} else {
			if ( $image_src = wp_get_attachment_image_src( $thumbnail_id, 'full' ) ) {
				\update_post_meta( $event_id, '_wcs_image', $image_src[ 0 ] );					
			}
		}

	}
		
}