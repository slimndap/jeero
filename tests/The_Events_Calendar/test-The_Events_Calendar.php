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
		
		add_filter( 
			'jeero/mother/get/response/endpoint=subscriptions/a fake ID', 
			array( $this, 'get_mock_response_for_get_subscription' ), 
			10, 3 
		);

		add_filter( 'jeero/mother/get/response/endpoint=inbox', array( $this, 'get_mock_response_for_get_inbox' ), 10, 3 );
		
		$subscription = Jeero\Subscriptions\get_subscription( 'a fake ID' );
		$calendar = Jeero\Calendars\get_calendar( $this->calendar );
		
		$settings = array(
			'theater' => 'veezi',
			'calendar' => array( $this->calendar ),
		);
		
		$subscription->set( 'settings', $settings );
		$subscription->save();

		Jeero\Inbox\pickup_items();

		$args = array(
			'post_status' => 'any',
		);		
		$events = tribe_get_events( $args );	
		
		$expected = date( 'Ymd', time() + 48 * HOUR_IN_SECONDS );
		$actual = tribe_get_start_date( $events[0]->ID, true, 'Ymd' );
		
		$this->assertEquals( $expected, $actual );
			
		
	}


}