<?php
/**
 * Jeero adds all Shows to his Calendar.
 */
namespace Jeero\Calendars;

class Calendar {
	
	public $slug = 'calendar';
	
	public $name = 'Calendar';
	
	function __construct() {
		
	}
		
	/**
	 * Gets all fields for this calendar.
	 * 
	 * @since	1.4
	 * @since	1.5	Added a dedicated tab and activation checbox for each calendar. 
	 * @return	array
	 */
	function get_fields() {	
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