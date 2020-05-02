<?php
namespace Jeero\Calendars;

const JEERO_CALENDARS_THEATER_FOR_WORDPRESS_REF_KEY = 'jeero/theater_for_wordpress/ref';

/**
 * Theater_For_WordPress class.
 * 
 * @extends Calendar
 */
class Theater_For_WordPress extends Calendar {
	
	function __construct() {
		
		$this->slug = 'Theater_For_WordPress';
		$this->name = __( 'Theater for WordPress', 'jeero' );
		
		parent::__construct();

	}
	
	function get_event_by_ref( $ref, $theater ) {
		
		$args = array(
			'post_status' => 'any',
			'meta_query' => array(
				array(
					'key' => JEERO_CALENDARS_THE_EVENTS_CALENDAR_REF_KEY,
					'value' => $ref,					
				),
			),
		);
		
		$events = \tribe_get_events( $args );
		
		if ( empty( $events ) ) {
			return false;
		}
		
		return $events[ 0 ]->ID;
		
	}

}