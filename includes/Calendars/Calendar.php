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
		
		return array(
			array(
				'name' => 'calendar',
				'label' => 'Calendar plugin',
				'type' => 'checkbox',
				'choices' => get_active_calendars(),
				'required' => true,
			),
		);
		
	}
	
	function get( $key ) {
		return $this->{ $key };
	}
	
}