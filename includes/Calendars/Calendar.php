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
	 * @return	array
	 */
	function get_fields() {		
		return array();		
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