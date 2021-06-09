<?php
namespace Jeero\Calendars;

use Jeero\Helpers\Images as Images;

// Register new calendar.
register_calendar( __NAMESPACE__.'\\Sugar_Calendar' );

/**
 * Sugar_Calendar class.
 * @since	1.12
 * 
 * @extends Calendar
 */
class Sugar_Calendar extends Calendar {

	function __construct() {
		
		$this->slug = 'Sugar_Calendar';
		$this->name = __( 'Sugar Calendar', 'jeero' );
		
		parent::__construct();
		
	}
	
	/**
	 * Gets the default Twig template for the event content field.
	 * 
	 * @since	1.12
	 * @return	string
	 */
	function get_default_content_template() {
		
		ob_start();
		
?>{{ description|raw }}
{%% if tickets_url %%}

<a href="{{ tickets_url }}">%s</a>
{%% endif %%}<?php

		return sprintf( ob_get_clean(), __( 'Tickets', 'jeero' ) );
	}
	
	/**
	 * Gets the default Twig template for the event location field.
	 * 
	 * @since	1.12
	 * @return	string
	 */
	function get_default_location_template() {
		return '{{ venue.title }}{% if venue.city %}, {{ venue.city }}{% endif %}';
	}

	/**
	 * Gets a Sugar Calendar post ID by Jeero ref.
	 * 
	 * @since	1.12
	 * @param 	string 	$ref
	 * @param 	string	$theater
	 * @return	int
	 */
	function get_event_by_ref( $ref, $theater ) {
		
		error_log( sprintf( '[%s] Looking for existing %s item %s.', $this->get( 'name' ), $theater, $ref ) );
		
		$args = array(
			'post_type' => \sugar_calendar_get_event_post_type_id(),
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
	 * @since	1.12
	 * @return	array
	 */
	function get_fields( $subscription ) {
		
		$fields = parent::get_fields( $subscription );
		
		$fields = array_merge( $fields, $this->get_import_status_fields() );
		
		// Add a SC Calendars field. 
		$calendars = get_terms( array(
			'taxonomy' => sugar_calendar_get_calendar_taxonomy_id(),
			'hide_empty' => false,
		) );
		
		if ( !empty( $calendars ) ) {
			
			$choices = array();
			foreach( $calendars as $calendar ) {
				$choices[ $calendar->slug ] = $calendar->name;
			}
			
			$fields[] = array(
				'name' => sprintf( '%s/import/sc_calendar', $this->slug ),
				'label' => __( 'Add to calendar', 'jeero' ),
				'type' => 'checkbox',
				'choices' => $choices,
			);
			
		}
			
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

		/**
		 * Removed categories field.
		 * Sugar Calendar does not support categories.
		 */
		$filtered_fields = array();

		foreach( $fields as $field ) {
			if ( $field[ 'name' ] == sprintf( '%s/import/update/categories', $this->slug ) ) {
				continue;
			}
			
			$filtered_fields[] = $field;
		}

		return $filtered_fields;
		
	}

	function is_active() {
		return class_exists( '\Sugar_Calendar_Requirements_Check' );
	}
	
	/**
	 * Processes the data from an event in the inbox.
	 * 
	 * @since 	1.12
	 * @since	1.14		Added support for custom fields.	
	 *
	 */
	function process_data( $result, $data, $raw, $theater, $subscription ) {
		
		$result = parent::process_data( $result, $data, $raw, $theater, $subscription );
		
		if ( \is_wp_error( $result ) ) {			
			return $result;
		}
		
		$ref = $data[ 'ref' ];

		$post_args = array(
			'post_type' => \sugar_calendar_get_event_post_type_id(),
		);

		$event_start = $this->localize_timestamp( strtotime( $data[ 'start' ] ) );

		$event_args = array(
			'object_type' => 'post',
			'object_subtype' => \sugar_calendar_get_event_post_type_id(),
			'start' => date( 'Y-m-d H:i', $event_start ),
		);

		if ( !empty( $data[ 'end' ] ) ) {
			$event_end = $this->localize_timestamp( strtotime( $data[ 'end' ] ) );			
			$event_args[ 'end' ] = date( 'Y-m-d H:i', $event_end );
		}

		if ( !empty( $data[ 'venue' ] ) ) {
			$event_args[ 'location' ] = $this->apply_template( 
				'location', 
				$data, 
				$this->get_default_location_template(), 
				$subscription 
			);
		}

		if ( $post_id = $this->get_event_by_ref( $ref, $theater ) ) {

			error_log( sprintf( '[%s] Updating %s event %s / %d.', $this->name, $theater, $ref, $post_id ) );

			$post_args[ 'ID' ] = $post_id;

			if ( 'always' == $this->get_setting( 'import/update/title', $subscription, 'once' ) ) {
				$post_args[ 'post_title' ] = $this->get_title_value( $data, $subscription );
				$event_args[ 'title' ] = $post_args[ 'post_title' ];
			}
			
			if ( 'always' == $this->get_setting( 'import/update/description', $subscription, 'once' ) ) {
				$post_args[ 'post_content' ] = $this->get_content_value( $data, $subscription );
				$event_args[ 'content' ] = $post_args[ 'post_content' ];
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
			}
			
		} else {

			error_log( sprintf( '[%s] Creating %s event %s.', $this->name, $theater, $ref ) );	

			$post_args[ 'post_title' ] = $this->get_title_value( $data, $subscription );
			$post_args[ 'post_content' ] = $this->get_content_value( $data, $subscription );
			$post_args[ 'post_status' ] = $this->get_setting( 'import/status', $subscription, 'draft' );

			$event_args[ 'title' ] = $post_args[ 'post_title' ];
			$event_args[ 'content' ] = $post_args[ 'post_content' ];
			$event_args[ 'status' ] = $post_args[ 'post_status' ];
			
			$post_id = $this->insert_post( $post_args );

			if ( !empty( $data[ 'production' ][ 'img' ] ) ) {
				$thumbnail_id = Images\update_featured_image_from_url( 
					$post_id,
					$data[ 'production' ][ 'img' ]
				);
			}

			\add_post_meta( $post_id, $this->get_ref_key( $theater ), $data[ 'ref' ], true );
			
		}	
				
		$event_args[ 'object_id' ] = $post_id;		
		$event = \sugar_calendar_get_event_by_object( $post_id );

		if ( !empty( $event->id )) {
			\sugar_calendar_update_event( $event->id, $event_args );
		} else {
			\sugar_calendar_add_event( $event_args );
		}

		$calendars = $this->get_setting( 'import/sc_calendar', $subscription, false );

		if ( !$calendars ) {
			\wp_set_object_terms( $post_id, array(), sugar_calendar_get_calendar_taxonomy_id(), false  );			
		} else {
			\wp_set_object_terms( $post_id, $calendars, sugar_calendar_get_calendar_taxonomy_id(), false  );
		}

		$this->update_custom_fields( $post_id, $data, $subscription );

	}

}