<?php
namespace Jeero\Calendars;

use Jeero\Helpers\Images as Images;

/**
 * GDLR_Events class.
 *
 * Adds support for the Goodlayers Event Post Type plugin. 
 *
 * @since	1.3
 * 
 * @extends Calendar
 */
class GDLR_Events extends Calendar {

	function __construct() {
		
		$this->slug = 'GDLR_Events';
		$this->name = __( 'Goodlayers Event Post Type', 'jeero' );
		
		parent::__construct();
		
	}
	
	function get_event_by_ref( $ref, $theater ) {
		
		error_log( sprintf( '[%s] Looking for existing %s item %s.', $this->get( 'name' ), $theater, $ref ) );
		
		$args = array(
			'post_type' => $this->get_post_type(),
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
	
	function get_post_type() {
		
		global $theme_option;
	
		if ( empty( $theme_option[ 'event-slug' ] ) ) {
			return 'event';
		}
		
		return $theme_option[ 'event-slug' ];

	}
	
	function get_category_slug() {

		global $theme_option;
	
		if ( empty( $theme_option[ 'event-category-slug' ] ) ) {
			return 'event_category';
		}
		
		return $theme_option[ 'event-category-slug' ];
		
	}
	
	function get_post_option( $event_id ) {

		$post_option = \get_post_meta( $event_id, 'post-option', true );
		if ( empty( $post_option ) )  {
			return array();
		}
		return json_decode( $post_option, true ); 
		
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
		
		$args = array(
			'post_type' => $this->get_post_type(),
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
		
		$post_option = $this->get_post_option( $event_id );
		
		$post_option[ 'page-title' ] = $data[ 'production' ][ 'title' ];
		$post_option[ 'date' ] = date( 'Y-m-d', $event_start );
		$post_option[ 'time' ] = date( 'H:i', $event_start );
		$post_option[ 'buy-now' ] = $data[ 'tickets_url' ];
		$post_option[ 'location' ] = $data[ 'venue' ][ 'title' ];
		
		// Set status
		if ( ! empty( $data[ 'status' ] ) ) {
			
			switch ( $data[ 'status' ] ) {
				
				case 'cancelled':
					$status = 'cancelled';
					break;
					
				case 'soldout':
					$status = 'sold-out';
					break;
					
				default:
					$status = 'buy-now';
				
			}
		
			$post_option[ 'status' ] = $status;
			
		}
		
		// Set prices
		if ( ! empty( $data[ 'prices' ] ) ) {
			$prices = array();
			
			foreach( $data[ 'prices' ] as $price ) {
				$prices[] = $price[ 'title' ].' '.\number_format_i18n( $price[ 'amount' ], 2 );
			}
			
			$post_option[ 'number' ] = implode( '<br>', $prices );
		}			
		
		\update_post_meta( $event_id, 'post-option', json_encode( $post_option ) );
		\update_post_meta( $event_id, 'gdlr-event-date', date( 'Y-m-d H:i', $event_start ) );

		if ( !empty( $data[ 'production' ][ 'img' ] ) ) {
			
			$thumbnail_id = Images\add_image_to_library( 
				$data[ 'production' ][ 'img' ],
				$event_id
			);
			
			if ( ! \is_wp_error( $thumbnail_id ) ) {
				\update_post_meta( $event_id, '_thumbnail_id', $thumbnail_id );					
			}

		}
		
		if ( ! empty( $data[ 'production' ][ 'categories' ] ) ) {
			\wp_set_object_terms( $event_id, $data[ 'production' ][ 'categories' ], $this->get_category_slug(), false );
		}

		return $event_id;		
		
	}
	
}
