<?php
namespace Jeero\Calendars;

const JEERO_CALENDARS_THEATER_FOR_WORDPRESS_REF_KEY = 'jeero/theater_for_wordpress/ref';

// Register new calendar.
register_calendar( __NAMESPACE__.'\\Theater_For_WordPress' );

/**
 * Theater_For_WordPress class.
 * 
 * @extends Calendar
 */
class Theater_For_WordPress extends Post_Based_Calendar {

	function __construct() {
		
		$this->slug = 'Theater_For_WordPress';
		$this->name = __( 'Theater for WordPress', 'jeero' );
		$this->categories_taxonomy = 'category';
		
		parent::__construct();

	}
	
	/**
	 * Checks if this calendar is active.
	 * 
	 * @since	1.15
	 * @return	bool
	 */
	function is_active() {
		return class_exists( '\WP_Theatre' );
	}
	
	/**
	 * Gets an event post by its ref and theater values.
	 * 
	 * @since	1.?
	 * @since	1.20		Fixed finding events that were previously imported by one of the 
	 *					Theater for WordPress import extensions.
	 *
	 * @param 	string			$ref
	 * @param 	string 			$theater
	 * @return 	WP_POST|bool					The event post or <false> if not found.
	 */
	function get_event_by_ref( $ref, $theater ) {
		
		// Find event by Jeero ref key.
		$event_id = parent::get_event_by_ref( $ref, $theater );		
		if ( $event_id ) {
			return $event_id;
		}
		
		// Find event that was previously imported by a WPT_Importer.
		$importer = new \WPT_Importer();
		$importer->set( 'slug', sprintf( 'wpt_%s', $theater ) );
		if ( $event = $importer->get_production_by_ref( $ref ) ) {
			return $event->ID;	
		}
		
		return false;
		
	}
	
	function get_post_ref( $data ) {

		if ( !empty( $data[ 'production' ][ 'ref' ] ) ) {
			$ref = $data[ 'production' ][ 'ref' ];
		} else {
			$ref = 'e'.$data[ 'ref' ];		
		}

		return $ref;
				
	}
	
	function get_post_type() {
		return \WPT_Production::post_type_name;
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
	 * @since	1.10		Added support for title and content Twig templates.
	 * @since	1.14		Added support for custom fields.	
	 * @since	1.15.1	Fix: event status was not set properly.
	 * @since	1.15.4	Fix: force floats for events prices to make them match the 
	 *					sanitazion happening inside the Theater for WordPress plugin.
	 *					@see https://github.com/slimndap/jeero/issues/6 
	 * @since	1.20		Clean up event dates that were previously imported by 
	 *					one of the Theater for WordPress import extensions.
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

		// Mimic the importer of the ActiveTickets for WordPress plugin.
		$legacy_importer = new \WPT_Importer();
		$legacy_importer->set( 'slug', sprintf( 'wpt_%s', $theater ) );
		
		$ref = $this->get_post_ref( $data );

		if ( $post_id = $this->get_event_by_ref( $ref, $theater ) ) {

			$event_args = array(
				'production' => $post_id,
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
						$event_args[ 'prices' ][] = (float) $price[ 'amount' ];					
					} else {
						$event_args[ 'prices' ][] = sprintf( '%s|%s', (float) $price[ 'amount' ], $price[ 'title' ] );
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
			
			// Replace date that was previously imported by a Theater for WordPress extension.
			if ( $legacy_date = $legacy_importer->get_event_by_ref( $data[ 'ref' ] ) ) {
				$this->log( sprintf( 'Deleting legacy %s event date %s.', $theater, $legacy_date->ID ) );
				\wp_delete_post( $legacy_date->ID, true );
			}
			
		}
		
		return $post_id;
	}
	
}