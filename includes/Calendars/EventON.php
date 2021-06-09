<?php
namespace Jeero\Calendars;

use Jeero\Helpers\Images as Images;

register_calendar( __NAMESPACE__.'\\EventON' );

/**
 * Theater_For_WordPress class.
 * 
 * @extends Calendar
 */
class EventON extends Calendar {

	function __construct() {
		
		$this->slug = 'EventON';
		$this->name = __( 'EventON', 'jeero' );
		
		parent::__construct();

	}
	
	function is_active() {
		return class_exists( '\EventON' );
	}

	function get_event_by_ref( $ref, $theater ) {
		
		error_log( sprintf( '[%s] Looking for existing %s item %s.', $this->get( 'name' ), $theater, $ref ) );
		
		$args = array(
			'post_type' => 'ajde_events',
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

	function get_fields( $subscription ) {
		
		$fields = parent::get_fields( $subscription );
		
		$fields = array_merge( $fields, $this->get_import_status_fields() );


		$fields = array_merge( $fields, $this->get_import_update_fields() );

		$fields[] = array(
			'name' => sprintf( '%s/import/category_taxonomy', $this->slug ),
			'label' => __( 'Import categories as ', 'jeero' ),
			'type' => 'select',
			'choices' => array(
				'post_tag' => __( 'Tags' ),
				'event_type' => __( 'Event Type', 'jeero' ),
				'event_type_2' => __( 'Event Type 2', 'jeero' ),
			),
		);

		$fields = array_merge( $fields, $this->get_custom_fields_fields( $subscription ) );
		
		$new_fields = array();
		
		foreach( $fields as $field )  {

			$new_fields[] = $field;
			
			if ( sprintf( '%s/import/template/content', $this->slug ) == $field[ 'name' ] ) {

				$new_fields[] =	array(
					'name' => sprintf( '%s/import/template/subtitle', $this->slug ),
					'label' => __( 'Event subtitle', 'jeero' ),
					'type' => 'template',
				);
				
			}
		}

		return $new_fields;		
		
	}
	
	function get_location_id( $title ) {
		$location_id = wp_cache_get( $title, 'jeero/location_id' );

		if ( false === $location_id ) {
		
			$location_term = get_term_by( 'name', $title, 'event_location' );
			
			if ( !( $venue_post ) ) {
				$location_id = tribe_create_venue( 
					array( 
						'Venue' => $title,
					)
				);
			} else {
				$location_id = $location_post->ID;
			}
			
			wp_cache_set( $title, $location_id, 'jeero/location_id' );
			
		}
		
		return $location_id;		
	}
	
	function process_data( $result, $data, $raw, $theater, $subscription ) {
		
		$result = parent::process_data( $result, $data, $raw, $theater, $subscription );
		
		if ( \is_wp_error( $result ) ) {			
			return $result;
		}
		
		$ref = $data[ 'ref' ];

		$post_content = '';
		if ( !empty( $data[ 'production' ][ 'description' ] ) ) {
			$post_content = $data[ 'production' ][ 'description' ];
		}
		
		$args = array(
			'post_type' => 'ajde_events',
		);

		if ( $event_id = $this->get_event_by_ref( $ref, $theater ) ) {
			error_log( sprintf( '[%s] Updating event %s / %d.', $this->name, $ref, $event_id ) );
			
			$args[ 'ID' ] = $event_id;

			if ( 'always' == $this->get_setting( 'import/update/title', $subscription, 'once' ) ) {
				$args[ 'post_title' ] = $this->get_title_value( $data, $subscription );
			}
			
			if ( 'always' == $this->get_setting( 'import/update/description', $subscription, 'once' ) ) {
				$args[ 'post_content' ] = $this->get_content_value( $data, $subscription );
			}
						
			wp_update_post( $args );
			
			if ( 
				'always' == $this->get_setting( 'import/update/image', $subscription, 'once' ) && 
				!empty( $data[ 'production' ][ 'img' ] )
			) {
				$thumbnail_id = Images\update_featured_image_from_url( 
					$event_id,
					$data[ 'production' ][ 'img' ]
				);
			}

			if ( 'always' == $this->get_setting( 'import/update/categories', $subscription, 'once' ) ) {
				
				$category_taxonomoy = $this->get_setting( 'import/category_taxonomy', $subscription, 'event_type' );
				
				if ( empty( $data[ 'production' ][ 'categories' ] ) ) {
					\wp_set_object_terms( $event_id, array(), $category_taxonomoy, false  );			
				} else {
					\wp_set_object_terms( $event_id, $data[ 'production' ][ 'categories' ], $category_taxonomoy, false  );
				}
			}


		} else {
			
			error_log( sprintf( '[%s] Creating event %s.', $this->name, $ref ) );

			$args[ 'post_title' ]= $this->get_title_value( $data, $subscription );
			$args[ 'post_content' ]= $this->get_content_value( $data, $subscription );
			$args[ 'post_status' ] = $this->get_setting( 'import/status', $subscription, 'draft' );

			$event_id = wp_insert_post( $args );

			\add_post_meta( $event_id, $this->get_ref_key( $theater ), $data[ 'ref' ] );

			if ( !empty( $data[ 'production' ][ 'img' ] ) ) {
				$thumbnail_id = Images\update_featured_image_from_url( 
					$event_id,
					$data[ 'production' ][ 'img' ]
				);
			}

		}		

		$event_start = $this->localize_timestamp( strtotime( $data[ 'start' ] ) );
		\update_post_meta( $event_id, '_start_hour', date( 'g', $event_start ) );
		\update_post_meta( $event_id, '_start_minute', date( 'I', $event_start ) );
		\update_post_meta( $event_id, '_start_ampm', date( 'a', $event_start ) );
		\update_post_meta( $event_id, 'evcal_srow', $event_start );
				
		if ( !empty( $data[ 'end' ] ) ) {
			$event_end = $this->localize_timestamp( strtotime( $data[ 'end' ] ) );
			\update_post_meta( $event_id, '_end_hour', date( 'g', $event_end ) );
			\update_post_meta( $event_id, '_end_minute', date( 'I', $event_end ) );
			\update_post_meta( $event_id, '_end_ampm', date( 'a', $event_end ) );
			\update_post_meta( $event_id, 'evcal_erow', $event_end );
		}

		if ( !empty( $data[ 'tickets_url' ] ) ) {
			\update_post_meta( $event_id, 'evcal_lmlink', $data[ 'tickets_url' ] );
		}
		
		if ( !empty( $data[ 'venue' ] ) ) {
			\wp_set_object_terms( $event_id, $data[ 'venue' ][ 'title' ], 'event_location', false  );
		}
		
		$tickets_status = 'scheduled';
		if ( !empty( $data[ 'status' ] ) and 'cancelled' == $data[ 'status' ] ) {
			$tickets_status = 'cancelled';
		}
		\update_post_meta( $event_id, '_status', $tickets_status );
		
		\update_post_meta( $event_id, 'evcal_subtitle', $this->apply_template( 
			'subtitle', 
			$data, 
			'', 
			$subscription 
		) );

		$this->update_custom_fields( $event_id, $data, $subscription );
				
		return $event_id;

	}
	
}
