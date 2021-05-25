<?php
namespace Jeero\Calendars;

use Jeero\Helpers\Images as Images;

/**
 * WP_Event_Manager class.
 * @since	1.13
 * 
 * @extends Calendar
 */
class WP_Event_Manager extends Calendar {

	function __construct() {
		
		$this->slug = 'WP_Event_Manager';
		$this->name = __( 'WP Event Manager', 'jeero' );
		
		parent::__construct();
		
	}
	
	/**
	 * Gets the default Twig template for the event location field.
	 * 
	 * @since	1.13
	 * @return	string
	 */
	function get_default_location_template() {
		return '{{ venue.title }}{% if venue.city %}, {{ venue.city }}{% endif %}';
	}

	/**
	 * Gets a WP_Event_Manager post ID by Jeero ref.
	 * 
	 * @since	1.13
	 * @param 	string 	$ref
	 * @param 	string	$theater
	 * @return	int
	 */
	function get_event_by_ref( $ref, $theater ) {
		
		error_log( sprintf( '[%s] Looking for existing %s item %s.', $this->get( 'name' ), $theater, $ref ) );
		
		$args = array(
			'post_type' => 'event_listing',
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
	 * @since	1.13
	 * @return	array
	 */
	function get_fields( $subscription ) {
		
		$fields = parent::get_fields( $subscription );
		
		$fields = array_merge( $fields, $this->get_import_update_fields() );
		$fields = array_merge( $fields, $this->get_custom_fields_fields( $subscription ) );

		$fields[] = 	array(
			'name' => sprintf( '%s/import/template/location', $this->slug ),
			'label' => __( 'Event location template', 'jeero' ),
			'type' => 'template',
			'instructions' => sprintf( 
				__( 'Leave empty to use the default template: <code>%s</code>.', 'jeero' ),
				esc_html( $this->get_default_location_template() )
			),
		);

		return $fields;
		
	}

	/**
	 * Gets the WP_Event_Manager venue ID by venue title.
	 *
	 * Creates a new venue if no venue is found.
	 * 
	 * @since	1.13
	 * @param 	string	$title
	 * @return	int
	 */
	function get_venue_id( $title ) {
		$venue_id = wp_cache_get( $title, 'jeero/venue_id' );

		if ( false === $venue_id ) {
		
			$venue_post = get_page_by_title( $title, OBJECT, 'event_venue' );
			
			if ( !( $venue_post ) ) {
				$args = array(
					'post_type' => 'event_venue',
					'post_status' => 'publish',
					'post_title' => $title,
					'post_content' => '',
				);
				
				$venue_id = \wp_insert_post( $args );

			} else {
				$venue_id = $venue_post->ID;
			}
			
			wp_cache_set( $title, $venue_id, 'jeero/venue_id' );
			
		}
		
		return $venue_id;		
	}
	
	/**
	 * Processes the data from an event in the inbox.
	 * 
	 * @since 	1.13
	 */
	function process_data( $result, $data, $raw, $theater, $subscription ) {
		
		$result = parent::process_data( $result, $data, $raw, $theater, $subscription );
		
		if ( \is_wp_error( $result ) ) {			
			return $result;
		}
		
		$ref = $data[ 'ref' ];

		$post_args = array(
			'post_type' => 'event_listing',
			'post_status' => 'publish',
			'comment_status' => 'closed',
		);

		if ( $post_id = $this->get_event_by_ref( $ref, $theater ) ) {

			error_log( sprintf( '[%s] Updating %s event %s / %d.', $this->name, $theater, $ref, $post_id ) );

			$post_args[ 'ID' ] = $post_id;

			if ( 'always' == $this->get_setting( 'import/update/title', $subscription, 'once' ) ) {
				$post_args[ 'post_title' ] = $this->get_title_value( $data, $subscription );
			}
			
			if ( 'always' == $this->get_setting( 'import/update/description', $subscription, 'once' ) ) {
				$post_args[ 'post_content' ] = $this->get_content_value( $data, $subscription );
			}
			
			$this->update_post( $post_args );

			if ( 
				'always' == $this->get_setting( 'import/update/image', $subscription, 'once' ) && 
				!empty( $data[ 'production' ][ 'img' ] )
			) {
				$thumbnail_id = Images\update_featured_image_from_url( 
					$post_id,
					$data[ 'production' ][ 'img' ]
				);
				\update_post_meta( $post_id, '_event_banner', \get_the_post_thumbnail_url( $post_id ) );
			}

			if ( 'always' == $this->get_setting( 'import/update/categories', $subscription, 'once' ) ) {
				if ( empty( $data[ 'production' ][ 'categories' ] ) ) {
					\wp_set_object_terms( $post_id, array(), 'tribe_events_cat', false  );			
				} else {
					\wp_set_object_terms( $post_id, $data[ 'production' ][ 'categories' ], 'event_listing_category', false  );
				}
			}
			
		} else {

			error_log( sprintf( '[%s] Creating %s event %s.', $this->name, $theater, $ref ) );	

			$post_args[ 'post_title' ] = $this->get_title_value( $data, $subscription );
			$post_args[ 'post_content' ] = $this->get_content_value( $data, $subscription );

			$post_id = $this->insert_post( $post_args );

			if ( !empty( $data[ 'production' ][ 'img' ] ) ) {
				$thumbnail_id = Images\update_featured_image_from_url( 
					$post_id,
					$data[ 'production' ][ 'img' ]
				);
				\add_post_meta( $post_id, '_event_banner', \get_the_post_thumbnail_url( $post_id ), true );
			}

			\add_post_meta( $post_id, '_event_online', 'no', true );

			\add_post_meta( $post_id, $this->get_ref_key( $theater ), $data[ 'ref' ], true );
			
			if ( !empty( $data[ 'production' ][ 'categories' ] ) ) {
				\wp_set_object_terms( $post_id, $data[ 'production' ][ 'categories' ], 'event_listing_category', false  );
			}
			
		}	
		
		\update_post_meta( $post_id, '_registration', $data[ 'tickets_url' ] );
		
		if ( !empty( $data[ 'venue' ] ) ) {
			\update_post_meta( $post_id, '_event_venue_ids', $this->get_venue_id( $data[ 'venue' ][ 'title' ] ) );
			if ( !empty( $data[ 'venue' ][ 'city' ] ) ) {
				\update_post_meta( $post_id, '_event_location', $this->apply_template( 
					'location', 
					$data, 
					$this->get_default_location_template(), 
					$subscription 
				) );
			}
		}

		$event_start = $this->localize_timestamp( strtotime( $data[ 'start' ] ) );
		\update_post_meta( $post_id, '_event_start_date', date( 'Y-m-d H:i:s', $event_start ) );
		\update_post_meta( $post_id, '_event_start_time', date( 'H:i:s', $event_start ) );
		
		if ( !empty( $data[ 'end' ] ) ) {
			$event_end = $this->localize_timestamp( strtotime( $data[ 'end' ] ) );			
			\update_post_meta( $post_id, '_event_end_date', date( 'Y-m-d H:i:s', $event_end ) );
			\update_post_meta( $post_id, '_event_end_time', date( 'H:i:s', $event_end ) );
		}		

	}

}