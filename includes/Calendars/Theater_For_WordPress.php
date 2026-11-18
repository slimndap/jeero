<?php
namespace Jeero\Calendars;

use Jeero\Abilities\Data as Event_Data;

const JEERO_CALENDARS_THEATER_FOR_WORDPRESS_REF_KEY = 'jeero/theater_for_wordpress/ref';

// Register new calendar.
register_calendar( __NAMESPACE__.'\\Theater_For_WordPress' );

add_filter( 'wpt/event/template/field/value', __NAMESPACE__ . '\\render_theater_for_wordpress_tickets_button_field', 10, 5 );

/**
 * Render Jeero's tickets button inside Theater for WordPress event templates.
 *
 * @since 1.35
 *
 * @param string     $value   Field value.
 * @param string     $field   Template field name.
 * @param array      $args    Field args.
 * @param array      $filters Field filters.
 * @param \WPT_Event $event   Theater for WordPress event.
 * @return string
 */
function render_theater_for_wordpress_tickets_button_field( $value, $field, $args, $filters, $event ) {

	if ( 'jeero_tickets_button' !== $field || ! $event instanceof \WPT_Event ) {
		return $value;
	}

	$subscription_id = get_post_meta( $event->ID, 'jeero/import/post/subscription', true );

	if ( empty( $subscription_id ) || ! is_scalar( $subscription_id ) ) {
		return '';
	}

	return jeero_get_theater_widget(
		'tickets_button',
		(string) $subscription_id,
		array(
			'event_id' => $event->ID,
		)
	);

}

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
	 * Gets standardized Jeero performances with their linked WordPress page details.
	 *
	 * @since	1.?
	 *
	 * @param	array	$input	The optional subscription, reference, category, text and date filters.
	 * @return	array[]|\WP_Error	The normalized performance data, or a read or validation error.
	 */
	function get_events( $input = array() ) {
		$valid = Event_Data\validate_filters( $input );
		if ( is_wp_error( $valid ) ) {
			return $valid;
		}
		global $wp_theatre, $wpdb;
		if ( ! $this->is_active() || ! $wp_theatre ) {
			return array();
		}
		$wpdb->last_error = '';
		$performances = $wp_theatre->events->get( array( 'status' => 'any' ) );
		if ( $wpdb->last_error ) {
			return new \WP_Error( 'jeero_storage', 'Unable to read Theater performances.' );
		}
		$events = array();
		foreach ( $performances as $performance ) {
			$production = $performance->production();
			if ( ! $production ) {
				continue;
			}
			$subscription_id = get_post_meta( $performance->ID, 'jeero/import/post/subscription', true ) ?: get_post_meta( $production->ID, 'jeero/import/post/subscription', true );
			$ref = get_post_meta( $performance->ID, '_wpt_source_ref', true );
			if ( '' === $subscription_id || '' === $ref ) {
				continue;
			}
			$source = get_post_meta( $performance->ID, '_jeero_event_data', true );
			if ( ! is_array( $source ) ) {
				continue;
			}
			$event = Event_Data\prepare_event( $source );
			if ( is_wp_error( $event ) ) {
				return $event;
			}
			$event = $this->get_calendar_event_data( $production->post(), $event );
			$event['subscription_id'] = (string) $subscription_id;
			$event['theater'] = get_post_meta( $performance->ID, '_wpt_source', true );
			$event['created_at'] = $source['created_at'] ?? '';
			$event['updated_at'] = $source['updated_at'] ?? '';
			$events[] = Event_Data\clean( $event );
		}
		return array_values( array_filter( Event_Data\unique_events( $events ), function( $event ) use ( $input ) {
			return Event_Data\matches( $event, $input );
		} ) );
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
	 * Gets all post fields for Theater for WordPress productions and event dates.
	 *
	 * @since 1.35
	 *
	 * @return array
	 */
	function get_post_fields() {

		$post_fields = parent::get_post_fields();

		$post_fields[] = array(
			'name' => 'remark',
			'title' => __( 'Event date remark', 'jeero' ),
			'template' => '',
		);

		return $post_fields;

	}
	
	/**
	 * Imports a production and its performance into Theater for WordPress.
	 *
	 * @since 	1.?
	 * @since	1.3.2	Added support for ticket status.
	 * @since	1.4		Added support for import settings to decide whether to
	 * 					overwrite title/description/image during import.
	 * 					Added support for post status settings during import.
	 * @since	1.6		Added support for categories.
	 * 					Added support for city.
	 * @since	1.10		Added support for title and content Twig templates.
	 * @since	1.14		Added support for custom fields.
	 * @since	1.15.1	Fix: event status was not set properly.
	 * @since	1.15.4	Fix: force floats for events prices to make them match the
	 * 					sanitazion happening inside the Theater for WordPress plugin.
	 * 					@see https://github.com/slimndap/jeero/issues/6
	 * @since	1.20		Clean up event dates that were previously imported by
	 * 					one of the Theater for WordPress import extensions.
	 * @since	1.35		Added support for event date remark templates.
	 * @since	1.?	Stores Jeero data per performance and propagates import errors.
	 *
	 * @param	mixed                            	$result      	The result of previous inbox processing.
	 * @param	array                            	$data        	The structured event data.
	 * @param	mixed                            	$raw         	The raw connector response.
	 * @param	string                           	$theater     	The ticketing system name.
	 * @param	\Jeero\Subscriptions\Subscription	$subscription	The subscription.
	 * @return	int|\WP_Error	The parent production post ID, or an import error.
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

			$existing_wpt_event = $importer->get_event_by_ref( $data[ 'ref' ] );
			$wpt_event = $importer->update_event( $event_args );
			if ( is_wp_error( $wpt_event ) ) {
				return $wpt_event;
			}
			if ( ! $wpt_event || empty( $wpt_event->ID ) ) {
				return new \WP_Error( 'jeero_storage', 'No imported Theater performance is available for Jeero data.' );
			}

			$this->delete_ticket_context_meta( $post_id );

			$post_fields = $this->get_setting( 'import/post_fields', $subscription );
			$update_remark = empty( $existing_wpt_event ) || (
				!empty( $post_fields[ 'remark' ][ 'update' ] ) &&
				'always' == $post_fields[ 'remark' ][ 'update' ]
			);

			if ( $update_remark ) {
				$remark = $this->get_rendered_template( 'remark', $data, $subscription );

				if ( '' === trim( $remark ) ) {
					delete_post_meta( $wpt_event->ID, 'remark' );
				} else {
					update_post_meta( $wpt_event->ID, 'remark', $remark );
				}
			}
			
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

			$updated = $this->update_ticket_context_meta( $wpt_event->ID, $data, $subscription );
			if ( is_wp_error( $updated ) ) {
				return $updated;
			}
			
			// Replace date that was previously imported by a Theater for WordPress extension.
			if ( $legacy_date = $legacy_importer->get_event_by_ref( $data[ 'ref' ] ) ) {
				$this->log( sprintf( 'Deleting legacy %s event date %s.', $theater, $legacy_date->ID ) );
				\wp_delete_post( $legacy_date->ID, true );
			}
			
		}
		
		return $post_id;
	}

	/**
	 * Deletes ticket context and supplementary Jeero event data from a production.
	 *
	 * @since 1.35
	 * @since	1.?	Also removes supplementary Jeero event data from the production.
	 *
	 * @param	int	$post_id	The production post ID.
	 * @return	void
	 */
	function delete_ticket_context_meta( $post_id ) {

		delete_post_meta( $post_id, 'jeero/import/post/tickets_url' );
		delete_post_meta( $post_id, 'jeero/import/post/tickets_status' );
		delete_post_meta( $post_id, 'jeero/import/post/widgets/tickets_inline/url' );
		delete_post_meta( $post_id, '_jeero_event_data' );
		delete_post_meta( $post_id, '_jeero_event_calendar' );

	}
	
}
