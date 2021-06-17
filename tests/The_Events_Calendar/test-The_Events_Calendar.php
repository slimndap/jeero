<?php
class The_Events_Calendar_Test extends Post_Based_Calendar_Test {
	
	function __construct() {
		
		parent::__construct();
		
		$this->calendar = 'The_Events_Calendar';
		
	}
	
	function get_events( $args = array() ) {

		$defaults = array(
			'cache_buster' => wp_generate_uuid4( ),
		);
		
		$args = wp_parse_args( $args, $defaults );
		
		return \tribe_get_events( $args );
		
	}
	
	function test_event_has_startdate() {
		
		$this->import_event();

		$args = array(
			'post_status' => 'any',
		);		
		$events = tribe_get_events( $args );	
		
		$expected = date( 'Ymd', time() + 48 * HOUR_IN_SECONDS );
		$actual = tribe_get_start_date( $events[0]->ID, true, 'Ymd' );
		
		$this->assertEquals( $expected, $actual );
			
		
	}

	/**
	 * Tests if The Events Calendar imports the correct start and end times.
	 *
	 * Until 0.15.2 Jeero was incorrectly localizing start and end times.
	 *
	 * @since	0.15.3
	 */
	function test_has_correct_times() {
		
		// Set website time zone to non-UTC.
		update_option( 'gmt_offset', +2 );
		
		$this->import_event();

		$args = array(
			'post_status' => 'any',
			'meta_query' => array(
				array(
					'key' => 'jeero/the_events_calendar/veezi/ref',
					'value' => 123,					
				),
			),
		);
		
		$events = \tribe_get_events( $args );

		$actual = tribe_get_start_time( $events[ 0 ]->ID, 'H:i' );
		$expected = date( 'H:i', current_time( 'timestamp' ) + 48 * HOUR_IN_SECONDS );
		$this->assertEquals( $expected, $actual );
		
		$actual = tribe_get_end_time( $events[ 0 ]->ID, 'H:i' );
		$expected = date( 'H:i', current_time( 'timestamp' ) + 90 * MINUTE_IN_SECONDS + 48 * HOUR_IN_SECONDS );
		$this->assertEquals( $expected, $actual );
		
	}


}