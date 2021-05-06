<?php
/**
 * Jeero adds all Shows to his Calendar.
 */
namespace Jeero\Calendars;

use \Handlebars\Handlebars;
use \Twig;

class Calendar {
	
	public $slug = 'calendar';
	
	public $name = 'Calendar';
	
	function __construct() {
		
	}
	
	function apply_template( $context, $data, $default, $subscription ) {
		
		$template = $this->get_setting( 'import/template/'.$context, $subscription );
		
		if ( empty( $template ) ) {
			return $default;
		}
				
		if ( empty( $data[ 'custom' ] ) ) {
			return $default;
		}
		
		$template_data = array(
			'title' => $data[ 'production' ]	[ 'title' ],
			'description' => $data[ 'production' ][ 'description' ],
		);		
		$template_data = array_merge( $template_data, $data[ 'custom' ] );
		
		$loader = new \Twig\Loader\ArrayLoader([
			'index' => 'Hello {{ name }}!',
		]);
		$twig = new \Twig\Environment($loader);
		
		$handlebars = new Handlebars();

		try {
			$output = $handlebars->render( $template, $template_data );
		} catch( \LogicException $e ) {
			error_log( sprintf( '[%s] Rendering template for %s field failed: %s.', $this->get( 'name' ), $context, $e->getMessage() ) );			
			return $default;
		}
		
		return $output;		
		
	}
	
	/**
	 * Gets all fields for this calendar.
	 * 
	 * @since	1.4
	 * @since	1.5	Added a dedicated tab and activation checbox for each calendar. 
	 * @return	array
	 */
	function get_fields( $subscription ) {	
		return array(
			
			array(
				'name' => $this->slug,
				'type' => 'tab',
				'label' => $this->name,
			),
			array(
				'name' => 'calendar',
				'label' => __( 'Enable import', 'jeero' ),
				'type' => 'checkbox',
				'choices' => array(
					$this->slug => sprintf( __( 'Enable %s import', 'jeero' ), $this->name ),
				),
			),
			
		);
	}
	
	/**
	 * Gets (optional) import update setting fields.
	 * 
	 * @since	1.4
	 * @since	1.6	Added category field.
	 *
	 * @return	array
	 */
	function get_import_update_fields() {
		
		$fields = array();

		$import_choices = array(
			'once' => __( 'on first import', 'jeero' ),
			'always' => __( 'on every import', 'jeero' ),
		);
		
		$import_fields = array(
			'title' => __( 'event title', 'jeero' ),
			'description' => __( 'event description', 'jeero' ),
			'image' => __( 'event image', 'jeero' ),
			'categories' => __( 'event categories', 'jeero' ),
		);
		
		foreach( $import_fields as $name => $label ) {
			$fields[] = array(
				'name' => sprintf( '%s/import/update/%s', $this->slug, $name ),
				'label' => sprintf( __( 'Update %s', 'jeero' ), $label ),
				'type' => 'select',
				'choices' => $import_choices,
			);
		}
				
		return $fields;		
	}
	
	/**
	 * Gets (optional) import status setting field.
	 * 
	 * @since	1.4
	 * @return	array
	 */
	function get_import_status_fields() {

		$fields = array( 
			array(
				'name' => sprintf( '%s/import/status', $this->slug ),
				'label' => __( 'Status for new events', 'jeero' ),
				'type' => 'select',
				'choices' => array(
					'draft' => __( 'Draft' ),
					'publish' => __( 'Publish' ),
				),
			)
		);
		
		return $fields;
		
	}
	
	function get_template_fields( $subscription ) {
		
		$template_fields = array(
			array(
				'name' => 'title',
			),	
			array(
				'name' => 'description',
			),	
		);
		
		$template_fields = array_merge( $template_fields, $subscription->get( 'theater' )[ 'custom_fields' ] );
		
		return $template_fields;
				
	}
	
	function get_custom_fields_fields( $subscription ) {
		
		ob_start();
		
		?><p>You can use <a href="https://handlebarsjs.com/guide/expressions.html" target="_blank">handlebars</a> templates to customise the content of events.</p>
		<p>The following expressions are available in your templates:</p>
		<ul><?php
		
		foreach( $this->get_template_fields( $subscription ) as $field ) {
			?><li><code>{{<?php
				echo $field[ 'name' ]; 
			?>}}</code></li><?php
		}
		
		?></li><?php
		
		$instructions = ob_get_clean();
		
		$fields = array(
			array(
				'name' => 'template_instructions',
				'label' => $instructions,
				'type' => 'message',
			),
			array(
				'name' => sprintf( '%s/import/template/title', $this->slug ),
				'label' => __( 'Event title', 'jeero' ),
				'type' => 'text',
				'instructions' => __( 'The title of the event.', 'jeero' ),
			),
			array(
				'name' => sprintf( '%s/import/template/content', $this->slug ),
				'label' => __( 'Event content', 'jeero' ),
				'type' => 'textarea',
				'instructions' => __( 'The main content of the event.', 'jeero' ),
			),
		);
		
		return $fields;
	}
	
	function get_setting( $key, $subscription, $default = '' ) {
		
		$settings = $subscription->get( 'settings' );
		
		$key = sprintf( '%s/%s', $this->slug, $key );
		
		if ( empty( $settings[ $key ] ) ) {
			return $default;
		}
		
		return $settings[ $key ];
		
	}
	
	function get_ref_key( $theater ) {
		return sprintf( 'jeero/%s/%s/ref', $this->get( 'slug' ), $theater );
	}
	
	function get( $key ) {
		return $this->{ $key };
	}
	
	/**
	 * Imports the data from an event in the inbox.
	 * 
	 * @since 	1.?
	 * @since	1.4	Added the subscription param.
	 */
	function import( $result, $data, $raw, $theater, $subscription ) {
		
		error_log( sprintf( '[%s] Import of %s item started.', $this->get( 'name' ), $theater ) );

		$result = $this->process_data( $result, $data, $raw, $theater, $subscription );
		
		if ( \is_wp_error( $result ) ) {
			error_log( sprintf( '[%s] Import of %s item failed: %s.', $this->get( 'name' ), $theater, $result->get_error_message() ) );
			return;
		}
		
		error_log( sprintf( '[%s] Import of %s item successful.', $this->get( 'name' ), $theater ) );

		return $result;
		
	}
	
	function localize_timestamp( $timestamp ) {
		
		return $timestamp + get_option( 'gmt_offset' ) * HOUR_IN_SECONDS;
		
	}
	
	/**
	 * Processes the data from an event in the inbox.
	 * 
	 * @since 	1.?
	 * @since	1.4	Added the subscription param.
	 */
	function process_data( $result, $data, $raw, $theater, $subscription ) {

		if ( empty( $data[ 'ref' ] ) ) {			
			return new \WP_Error( 'jeero/import', 'Ref identifier is missing' );
		}
		
		return $result;
		
	}
	
}