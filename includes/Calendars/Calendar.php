<?php
/**
 * Jeero adds all Shows to his Calendar.
 */
namespace Jeero\Calendars;

class Calendar {
	
	public $slug = 'calendar';
	
	public $name = 'Calendar';
	
	function __construct() {
		
		//$this->load_fields();
		
	}
	
	function get_fields() {
		
		$choices = array();
		
		foreach( get_active_calendars() as $calendar ) {
			$choices[ $calendar->get( 'slug' ) ] = $calendar->get( 'name' );
		}
		
		return array(
			array(
				'name' => 'calendar',
				'label' => 'Calendar plugin',
				'type' => 'checkbox',
				'choices' => $choices,
				'required' => true,
			),
		);
		
	}
	
	function get( $key ) {
		return $this->{ $key };
	}
	
	function import( $data, $raw ) {
		
		$result = $this->process_data( $data, $raw );
		
		if ( \is_wp_error( $result ) ) {
			error_log( sprintf( '[%s] Import failed: %s.', $this->get( 'name' ), $result->get_error_message() ) );
			return;
		}
		
		error_log( sprintf( '[%s] Import successful.', $this->get( 'name' ) ) );

	}
	
	function process_data( $data, $raw ) {

		if ( empty( $data[ 'ref' ] ) ) {			
			return new \WP_Error( 'jeero/import', 'Ref identifier is missing' );
		}
		
		return $data;
		
	}
	
}