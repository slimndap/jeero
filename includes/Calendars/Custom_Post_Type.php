<?php
namespace Jeero\Calendars;

const JEERO_CALENDARS_CUSTOM_POST_TYPE_REF_KEY = 'jeero/custom_post_type/ref';

// Register new calendar.
register_calendar( __NAMESPACE__.'\\Custom_Post_Type' );

/**
 * Theater_For_WordPress class.
 * 
 * @extends Calendar
 */
class Custom_Post_Type extends Post_Based_Calendar {

	function __construct() {
		
		$this->slug = 'Custom_Post_Type';
		$this->name = __( 'Custom Post Type', 'jeero' );
		
		parent::__construct();

	}
	
	function get_setting_fields( $subscription ) {
		
		$fields = parent::get_setting_fields( $subscription );
		
		$filtered_fields = array();

		foreach( $fields as $field ) {

			$filtered_fields[] = $field;

			if ( 'calendar' == $field[ 'name' ] ) {

				$args = array(
					'public' => true,
					'_builtin' => false,
				);
				$post_types = \get_post_types( $args, 'objects' );
				$choices = array(
					''
				);
				foreach( $post_types as $post_type )	 {
					$choices[ $post_type->name ] = $post_type->label;
				}
				
				$filtered_fields[] = array(
					'name' => sprintf( '%s/import/custom_post_type', $this->slug ),
					'label' => __( 'Custom Post Type', 'jeero' ),
					'type' => 'select',
					'choices' => $choices,
					'instructions' => __( 'Eg. events for the same movie or events that are part of a festival.', 'jeero' ),
				);
			}
		}

		return $filtered_fields;
		
	}

	function process_data( $result, $data, $raw, $theater, $subscription ) {

		$this->post_type = $this->get_setting( 'import/custom_post_type', $subscription );

		$result = parent::process_data( $result, $data, $raw, $theater, $subscription );

		$event_id = $this->get_event_by_ref( $data[ 'ref' ], $theater );		
		if ( !$event_id ) {
			$this->log( sprintf( 'Import of event %s skipped: no existing post found.', $data[ 'ref' ] ) );
		}
		
		$menu_order = strtotime( $data[ 'start' ] );

		$event_args = array(
			'ID' => $event_id,
			'menu_order' => $menu_order,					
		);
		\wp_update_post( $event_args );

		return $result;

	}
	
}