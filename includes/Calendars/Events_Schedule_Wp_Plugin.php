<?php
namespace Jeero\Calendars;

use Jeero\Helpers\Images as Images;

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

	function do_footprint() {
		
		if ( !is_singular( 'class' ) ) {
			return;
		}
		
		$last_modified = get_the_modified_time();
		
		?>
<!--
	Event imported by Jeero. Learn more: https://jeero.ooo
	Last sync: <?php echo get_the_modified_date(); ?> <?php echo get_the_modified_time(); ?>
	
-->
		<?php

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

		$post_content = '';
		if ( !empty( $data[ 'production' ][ 'description' ] ) ) {
			$post_content = $data[ 'production' ][ 'description' ];
		}
		/*
		if ( !empty( $data[ 'production' ][ 'description' ] ) ) {
			$block = array(
				'blockName' => 'core/paragraph',
				'innerHTML' => 	$data[ 'production' ][ 'description' ],
				'innerContent' => array( $data[ 'production' ][ 'description' ] ),
				'attrs' => array(),
			);
			$post_content = \serialize_block( $block );
		}
		*/
		
		$args = array(
			'post_type' => 'class',
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
		
		\update_post_meta( $event_id, '_wcs_timestamp', $event_start );
		\update_post_meta( $event_id, '_wcs_action_label', __( 'Tickets', 'jeero' ) );
		\update_post_meta( $event_id, '_wcs_action_call', 1 );
		\update_post_meta( $event_id, '_wcs_action_custom', $data[ 'tickets_url' ] );
		\update_post_meta( $event_id, '_wcs_interval', 0 );

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

		return $event_id;
		
	}
	
}