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
	
	function get_ref_key( $theater ) {
		return sprintf( 'jeero/%s/%s/ref', $this->get( 'slug' ), $theater );
	}
	
	function get( $key ) {
		return $this->{ $key };
	}
	
	function import( $data, $raw, $theater ) {
		
		error_log( sprintf( '[%s] Import of %s item started.', $this->get( 'name' ), $theater ) );

		$result = $this->process_data( $data, $raw, $theater );
		
		if ( \is_wp_error( $result ) ) {
			error_log( sprintf( '[%s] Import of %s item failed: %s.', $this->get( 'name' ), $theater, $result->get_error_message() ) );
			return;
		}
		
		error_log( sprintf( '[%s] Import of %s item successful.', $this->get( 'name' ), $theater ) );

	}
	
	function localize_timestamp( $timestamp ) {
		
		return $timestamp + get_option( 'gmt_offset' ) * HOUR_IN_SECONDS;
		
	}
	
	function process_data( $data, $raw, $theater ) {

		if ( empty( $data[ 'ref' ] ) ) {			
			return new \WP_Error( 'jeero/import', 'Ref identifier is missing' );
		}
		
		return $data;
		
	}
	
}