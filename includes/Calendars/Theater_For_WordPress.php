<?php
namespace Jeero\Calendars;

use Jeero\Helpers\Images as Images;

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
	
	/**
	 * Gets all fields for this calendar.
	 * 
	 * @since	1.4
	 * @return	array
	 */
	function get_fields() {
		
		$fields = parent::get_fields();
		
		$fields = array_merge( $fields, $this->get_import_status_fields() );
		$fields = array_merge( $fields, $this->get_import_update_fields() );
		
		return $fields;
		
	}

	/**
	 * Processes the data from an event in the inbox.
	 * 
	 * @since 	1.?
	 * @since	1.3.2	Added support for ticket status.
	 * @since	1.4		Added support for import settings to decide whether to 
	 * 					overwrite title/description/image during import.
	 * 					Added support for post status settings during import.
	 * @since	1.6		Added support for categories.
	 *					Added support for city.
	 *
	 */
	function process_data( $result, $data, $raw, $theater, $subscription ) {
		
		$result = parent::process_data( $result, $data, $raw, $theater, $subscription );
		
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

		$import_defaults = array(
			$this->slug.'/import/title' => 'once',
			$this->slug.'/import/description' => 'once',
			$this->slug.'/import/image' => 'once',
		);
		$import_settings = wp_parse_args( $subscription->get( 'settings' ), $import_defaults );
			
		if ( $wpt_production = $importer->get_production_by_ref( $ref ) ) {
			
			error_log( sprintf( '[%s] Updating event %s / %d.', $this->name, $ref, $wpt_production->ID ) );
			
			$post = array(
				'ID' => $wpt_production->ID,
			);
			
			if ( 'always' == $this->get_setting( 'import/update/title', $subscription, 'once' ) ) {
				$post[ 'post_title' ] = $data[ 'production' ][ 'title' ];
			}
			
			if ( 'always' == $this->get_setting( 'import/update/description', $subscription, 'once' ) ) {
				$post[ 'post_content' ] = $data[ 'production' ][ 'description' ];
			}
			
			\wp_update_post( $post );
			
			if ( 
				'always' == $this->get_setting( 'import/update/image', $subscription, 'once' ) && 
				!empty( $data[ 'production' ][ 'img' ] )
			) {
				$this->update_image( $wpt_production	, $data[ 'production' ][ 'img' ] );
			}

			if ( 'always' == $this->get_setting( 'import/update/categories', $subscription, 'once' ) ) {
				if ( empty( $data[ 'production' ][ 'categories' ] ) ) {
					\wp_set_object_terms( $wpt_production->ID, array(), 'category', false  );			
				} else {
					\wp_set_object_terms( $wpt_production->ID, $data[ 'production' ][ 'categories' ], 'category', false  );
				}
			}
			
		} else {

			error_log( sprintf( '[%s] Creating event %d.', $this->name, $ref ) );
			
			$post = array(
				'post_type' => \WPT_Production::post_type_name,
				'post_title' => $data[ 'production' ][ 'title' ],
				'post_content' => $data[ 'production' ][ 'description' ],
				'post_status' => $this->get_setting( 'import/status', $subscription, 'draft' ),
			);
			
			$post_id = \wp_insert_post( $post, true );
			
			if ( \is_wp_error( $post_id ) ) {
				return $post_id;
			}

			\add_post_meta( $post_id, '_wpt_source', $theater, true );
			\add_post_meta( $post_id, '_wpt_source_ref', $ref, true );

			error_log( sprintf( '[%s] Created post %d.', $this->name, $post_id ) );
			
			$wpt_production = new \WPT_Production( $post_id );
			
			if ( !empty( $data[ 'production' ][ 'img' ] ) ) {
				$this->update_image( $wpt_production	, $data[ 'production' ][ 'img' ] );
			}
			
			if ( !empty( $data[ 'production' ][ 'categories' ] ) ) {
				\wp_set_object_terms( $wpt_production->ID, $data[ 'production' ][ 'categories' ], 'category', false  );
			}
			
		}


		
		$event_args = array(
			'production' => $wpt_production->ID,
			'venue' => $data[ 'venue' ][ 'title' ] ?? '',
			'city' => $data[ 'venue' ][ 'city' ] ?? '',
			'event_date' => $data[ 'start' ],
			'ref' => $data[ 'ref' ],
			'prices' => array(),
			'tickets_url' => $data[ 'tickets_url' ] ?? '',
		);
		
		if ( !empty( $data[ 'prices' ] ) ) {			
			foreach( $data[ 'prices' ] as $price ) {
				if ( empty( $price[ 'title' ] ) ) {
					$event_args[ 'prices' ][] = $price[ 'amount' ];					
				} else {
					$event_args[ 'prices' ][] = sprintf( '%s|%s', $price[ 'amount' ], $price[ 'title' ] );
				}
			}
		}
		
		$wpt_event = $importer->update_event( $event_args );
		
		if ( !empty( $data[ 'end' ] ) ) {
			update_post_meta( $wpt_event->ID, 'enddate', $data[ 'end' ] );
		}
		
		$tickets_status = \WPT_Event::tickets_status_onsale;
		if ( !empty( $data[ 'status' ] ) ) {
			switch( $data[ 'status' ] ) {
				case 'cancelled':
					$tickets_status = \WPT_Event::tickets_status_cancelled;
					break;
				case 'hidden':
					$tickets_status = \WPT_Event::tickets_status_hidden;
					break;
				case 'soldout':
					$tickets_status = \WPT_Event::tickets_status_soldout;
					break;
			}
		}

		update_post_meta( $wpt_event->ID, 'tickets_status', $tickets_status );

		return $wpt_event;
	}
	
	/**
	 * Updates the image of a production.
	 * 
	 * @since	1.4
	 * @param 	WPT_Production	$wpt_production
	 * @param	string 			$image_url
	 * @return 	void
	 */
	function update_image( $wpt_production, $image_url ) { 
		$thumbnail_id = Images\update_featured_image_from_url( 
			$wpt_production->ID,
			$image_url
		);

		if ( \is_wp_error( $thumbnail_id ) ) {
			error_log( sprintf( 'Updating thumbnail for event failed %s / %d.', $wpt_production->title(), $wpt_production->ID ) );
		}		
	}
	
}