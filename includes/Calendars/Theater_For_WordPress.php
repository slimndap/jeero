<?php
namespace Jeero\Calendars;

const JEERO_CALENDARS_THEATER_FOR_WORDPRESS_REF_KEY = 'jeero/theater_for_wordpress/ref';

/**
 * Theater_For_WordPress class.
 * 
 * @extends Calendar
 */
class Theater_For_WordPress extends Calendar {

	function __construct() {
		
		$this->slug = 'Theater_For_WordPress';
		$this->name = __( 'Theater for WordPress', 'jeero' );
		
		parent::__construct();

	}
	
	function get_event_by_ref( $ref, $theater ) {
				
		if ( $wpt_event = $this->importer->get_production_by_ref( $ref ) ) {
			return $wpt_event->ID;	
		}
		
		return false;
		
	}

	function process_data( $result, $data, $raw, $theater ) {
		
		$result = parent::process_data( $result, $data, $raw, $theater );
		
		if ( \is_wp_error( $result ) ) {			
			return $result;
		}
		
		$importer = new \WPT_Importer();
		$importer->set( 'slug', $theater );
		$importer->set( 'stats', array( 
			'events_created' => 0,
			'events_updated' => 0,
		) );

		if ( !empty( $data[ 'production' ][ 'ref' ] ) ) {
			$ref = $data[ 'production' ][ 'ref' ];
		} else {
			$ref = 'e'.$data[ 'ref' ];		
		}

		if ( $wpt_production = $importer->get_production_by_ref( $ref ) ) {
			
			error_log( sprintf( '[%s] Updating event %s / %d.', $this->name, $ref, $wpt_production->ID ) );
			
		} else {

			error_log( sprintf( '[%s] Creating event %d.', $this->name, $ref ) );
			
			$post = array(
				'post_type' => \WPT_Production::post_type_name,
				'post_title' => $data[ 'production' ][ 'title' ],
				'post_content' => $data[ 'production' ][ 'description' ],
				'post_status' => 'draft',
			);
			
			$post_id = \wp_insert_post( $post, true );
			
			if ( \is_wp_error( $post_id ) ) {
				return $post_id;
			}

			\add_post_meta( $post_id, '_wpt_source', $theater, true );
			\add_post_meta( $post_id, '_wpt_source_ref', $ref, true );

			error_log( sprintf( '[%s] Created post %d.', $this->name, $post_id ) );
			
			$wpt_production = new \WPT_Production( $post_id );
			
		}
		
		if ( !empty( $data[ 'production' ][ 'img' ] ) && !\has_post_thumbnail( $wpt_production->ID ) ) {

			add_featured_image( 
				$data[ 'production' ][ 'img' ], 
				$wpt_production->ID, 
				$data[ 'production' ][ 'title' ] 
			);
			
		}

		$event_args = array(
			'production' => $wpt_production->ID,
			'venue' => $data[ 'venue' ][ 'title' ] ?? '',
			'event_date' => $data[ 'start' ],
			'ref' => $data[ 'ref' ],
			'prices' => array(),
			'tickets_url' => $data[ 'tickets_url' ],
		);
		
		if ( !empty( $data[ 'prices' ] ) ) {			
			foreach( $data[ 'prices' ] as $price ) {
				$event_args[ 'prices' ][] = sprintf( '%s|%s', $price[ 'amount' ], $price[ 'title' ] );
			}
		}
		
		$wpt_event = $importer->update_event( $event_args );
		
		if ( !empty( $data[ 'end' ] ) ) {
			update_post_meta( $wpt_event->ID, 'enddate', $data[ 'end' ] );
		}
		
		return $wpt_event;
	}
	
}