<?php
namespace Jeero\Calendars;

// Register new calendar.
register_calendar( __NAMESPACE__.'\\Sugar_Calendar' );

/**
 * Sugar_Calendar class.
 * @since	1.12
 * 
 * @extends Calendar
 */
class Sugar_Calendar extends Post_Based_Calendar {

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
	 * Gets all fields for this calendar.
	 * 
	 * @since	1.12
	 * @return	array
	 */
	function get_setting_fields( $subscription ) {
		
		$fields = parent::get_setting_fields( $subscription );
		
		// Add a SC Calendars field. 
		$calendars = get_terms( array(
			'taxonomy' => \sugar_calendar_get_calendar_taxonomy_id(),
			'hide_empty' => false,
		) );
		
			
		$filtered_fields = array();

		foreach( $fields as $field ) {
			

			/**
			 * Removed categories field.
			 * Sugar Calendar does not support categories.
			 */
			if ( $field[ 'name' ] == sprintf( '%s/import/update/categories', $this->slug ) ) {
				continue;
			}
			
			$filtered_fields[] = $field;

			if ( 'calendar' == $field[ 'name' ] ) {

				if ( !empty( $calendars ) ) {
					
					$choices = array();
					foreach( $calendars as $calendar ) {
						$choices[ $calendar->slug ] = $calendar->name;
					}
					
					$filtered_fields[] = array(
						'name' => sprintf( '%s/import/sc_calendar', $this->slug ),
						'label' => __( 'Add to calendar', 'jeero' ),
						'type' => 'checkbox',
						'choices' => $choices,
					);
					
				}
				
			}

		}

		return $filtered_fields;
		
	}
	
	function get_post_fields() {
		$post_fields = parent::get_post_fields();
		
		$new_post_fields = array();
		
		foreach( $post_fields as $post_field ) {
			
			if ( 'content' == $post_field[ 'name' ] ) {
				$post_field[ 'template' ] = $this->get_default_content_template();
			}
			
			$new_post_fields[] = $post_field;
		}
		
		$new_post_fields[] = array(
			'name' => 'location',
			'title' => __( 'Event location', 'jeero' ),
			'template' => '{{ venue.title }}{% if venue.city %}, {{ venue.city }}{% endif %}',
		);
		
		return $new_post_fields;
	}
	
	function get_post_type() {
		return \sugar_calendar_get_event_post_type_id();
	}

	/**
	 * Detect if Sugar Calendar plugin is activated.
	 * 
	 * @since	1.12
	 * @since	1.15.4	Fixed detection for Sugar Calendar 2.2.
	 * @return	bool
	 */
	function is_active() {
		return class_exists( '\Sugar_Calendar\\Requirements_Check' );
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
		
		$ref = $this->get_post_ref( $data );

		if ( $post_id = $this->get_event_by_ref( $ref, $theater ) ) {
			
			$post = \get_post( $post_id );

			$event_start = strtotime( $data[ 'start' ] );
	
			$event_args = array(
				'object_type' => 'post',
				'object_subtype' => \sugar_calendar_get_event_post_type_id(),
				'start' => date( 'Y-m-d H:i', $event_start ),
				'title' => $post->post_title,
				'content' => $post->post_content,
				'status' => $post->post_status,
				'object_id' => $post_id,
			);
	
			if ( !empty( $data[ 'end' ] ) ) {
				$event_end = strtotime( $data[ 'end' ] );			
				$event_args[ 'end' ] = date( 'Y-m-d H:i', $event_end );
			}
	
			if ( !empty( $data[ 'venue' ] ) ) {
				$event_args[ 'location' ] = $this->get_rendered_template( 'location', $data, $subscription );				
			}

			$event = \sugar_calendar_get_event_by_object( $post_id );
			if ( !empty( $event->id )) {
				\sugar_calendar_update_event( $event->id, $event_args );
			} else {
				$sc_event_id = \sugar_calendar_add_event( $event_args );
			}

			$calendars = $this->get_setting( 'import/sc_calendar', $subscription, false );
	
			if ( !$calendars ) {
				\wp_set_object_terms( $post_id, array(), sugar_calendar_get_calendar_taxonomy_id(), false  );			
			} else {
				\wp_set_object_terms( $post_id, $calendars, sugar_calendar_get_calendar_taxonomy_id(), false  );
			}

		}	
				
		return $post_id;

	}

}