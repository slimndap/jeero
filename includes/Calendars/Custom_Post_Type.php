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
	
	function get_post_fields() {
				
		$post_fields = parent::get_post_fields();
		
		$filtered_post_fields = array();
		
		foreach( $post_fields as $post_field ) {
			
			if ( 'content' == $post_field[ 'name' ] ) {
				
				if ( !\post_type_supports( $this->get_post_type(), 'editor' ) ) {
					continue;
				}
				
			}
			
			if ( 'excerpt' == $post_field[ 'name' ] ) {
				
				if ( !\post_type_supports( $this->get_post_type(), 'excerpt' ) ) {
					continue;
				}
				
			}
			
			$filtered_post_fields[] = $post_field;
			
		}
	
		return $filtered_post_fields;
	
	}
			
	function get_post_ref( $data ) {

		$ref = parent::get_post_ref( $data );

		if ( !$this->use_event_dates() ) {
			return $ref;
		}
		
		if ( !empty( $data[ 'production' ][ 'ref' ] ) ) {
			$ref = $data[ 'production' ][ 'ref' ];
		} else {
			$ref = 'e'.$ref;		
		}

		return $ref;
				
	}
		
	function get_setting_fields( $subscription ) {
		
		$this->post_type = $this->get_setting( 'import/custom_post_type', $subscription );

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
					$choices[ $post_type->name ] = sprintf( '%s (%s)', $post_type->label, $post_type->name );
				}
				
				$filtered_fields[] = array(
					'name' => sprintf( '%s/import/custom_post_type', $this->slug ),
					'label' => __( 'Custom Post Type', 'jeero' ),
					'type' => 'select',
					'choices' => $choices,
				);
			}
		}

		return $filtered_fields;
		
	}

	function process_data( $result, $data, $raw, $theater, $subscription ) {

		$this->post_type = $this->get_setting( 'import/custom_post_type', $subscription );

		$result = parent::process_data( $result, $data, $raw, $theater, $subscription );

		$event_id = $this->get_event_by_ref( $this->get_post_ref( $data ), $theater );		
		if ( !$event_id ) {
			$this->log( sprintf( 'Import of event %s skipped: no existing post found.', $data[ 'ref' ] ) );
			return $result;
		}
		
		$menu_order = strtotime( $data[ 'start' ] );

		if ( $this->use_event_dates() ) {

			$this->log( sprintf( '%s can have parent-child relationships.', $this->get_post_type() ) );
			
			$date_ref = $data[ 'ref' ];
			$ref_key = $this->get_ref_key( $theater );

			$args = array(
				'post_type' => $this->get_post_type(),
				'post_status' => array( 'publish', 'draft' ),
				'meta_query' => array(
					array(
						'key' => $ref_key,
						'value' => $date_ref,
					),
				),
			);			
			$posts = \get_posts( $args );

			$date_args = array(
				'post_title' => $data[ 'production' ][ 'title'],
				'post_type' => $this->get_post_type(),
				'post_content' => '',		
				'post_excerpt' => '',
				'post_parent' => $event_id,
				'menu_order' => $menu_order,					
			);
			
			if ( empty( $posts ) ) {
				
				$this->log( sprintf( 'Creating date item %s for event item %d.', $date_ref, $event_id ) );
				
				$date_args[ 'post_status' ] = 'publish';
				
				$date_id = \wp_insert_post( $date_args, true );
	
				if ( \is_wp_error( $date_id ) ) {
					return $date_id;
				}
				
				\add_post_meta( $date_id, $ref_key, $date_ref, true );
				
			} else {

				$date_id = $posts[ 0 ]->ID;
				
				$this->log( sprintf( 'Updating date item %d for event item %d.', $date_id, $event_id ) );

				$date_args[ 'ID' ] = $date_id;
				
				\wp_update_post( $date_args );
				
			}
			
		} else {

			$this->log( sprintf( '%s can not have parent-child relationships.', $this->get_post_type() ) );

				
			$event_args = array(
				'ID' => $event_id,
				'menu_order' => $menu_order,					
			);
			\wp_update_post( $event_args );
			
		}

		return $result;

	}

	function use_event_dates() {
		
		$post_type_object = get_post_type_object( $this->get_post_type() );
		
		return $post_type_object && $post_type_object->hierarchical;
		
	}
	
}