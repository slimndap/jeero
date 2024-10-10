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

	function get_setting_fields( $subscription ) {
		
		$this->post_type = $this->get_setting( 'import/post_type', $subscription );

		$fields = parent::get_setting_fields( $subscription );
		
		$filtered_fields = array();

		foreach( $fields as $field ) {

			if ( 'calendar' == $field[ 'name' ] ) {

				$filtered_fields[] = $field;
			
				$args = array(
					'public' => true,
					'_builtin' => false,
				);
				$post_types = \get_post_types( $args, 'objects' );

				$choices = array(
					'' => sprintf( '(%s)', __( 'select a post type', 'jeero' ) ),
				);
				foreach( $post_types as $post_type )	 {
					$choices[ $post_type->name ] = sprintf( '%s (%s)', $post_type->label, $post_type->name );
				}				
				$filtered_fields[] = array(
					'name' => sprintf( '%s/import/post_type', $this->slug ),
					'label' => __( 'Post type for events', 'jeero' ),
					'type' => 'select',
					'choices' => $choices,
				);
				
				continue;
				
			}
				
			if ( empty( $this->get_post_type() ) ) {
				break;
			}

			if ( sprintf( '%s/import/update/categories', $this->slug ) == $field[ 'name' ] ) {

				$args = array(
					'public' => true,
					'_builtin' => false,
				);
				$taxonomies = \get_object_taxonomies( $this->get_post_type(), 'objects' );
				
				if ( empty( $taxonomies ) ) {
					continue;
				}

				$choices = array(
					'' => sprintf( '(%s)', __( 'select a taxonomy', 'jeero' ) ),
				);
				foreach( $taxonomies as $taxonomy )	 {
					$choices[ $taxonomy->name ] = sprintf( '%s (%s)', $taxonomy->label, $taxonomy->name );
				}				
				$filtered_fields[] = array(
					'name' => sprintf( '%s/import/categories_taxonomy', $this->slug ),
					'label' => __( 'Taxonomy for event categories', 'jeero' ),
					'type' => 'select',
					'choices' => $choices,
				);

				$filtered_fields[] = $field;
				continue;
				
			}

			if ( sprintf( '%s/import/update/image', $this->slug ) == $field[ 'name' ] ) {

				if ( !\post_type_supports( $this->get_post_type(), 'thumbnail' ) ) {
					continue;
				}
				
				$filtered_fields[] = $field;
				continue;				

			}
			
			$filtered_fields[] = $field;

		}

		return $filtered_fields;
		
	}

	function process_data( $result, $data, $raw, $theater, $subscription ) {

		$this->post_type = $this->get_setting( 'import/post_type', $subscription );
		$this->categories_taxonomy = $this->get_setting( 'import/categories_taxonomy', $subscription );

		return parent::process_data( $result, $data, $raw, $theater, $subscription );

	}

}