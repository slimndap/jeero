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
	
	function test_inbox_event_is_updated_after_second_import() {
		global $wp_theatre;
		
		add_filter( 
			'jeero/mother/get/response/endpoint=subscriptions/a fake ID', 
			array( $this, 'get_mock_response_for_get_subscription' ), 
			10, 3 
		);

		add_filter( 'jeero/mother/get/response/endpoint=inbox', array( $this, 'get_mock_response_for_get_inbox' ), 10, 3 );
		
		$subscription = Jeero\Subscriptions\get_subscription( 'a fake ID' );
		
		$settings = array(
			'theater' => 'veezi',
			'calendar' => array( $this->calendar ),
		);
		
		$subscription->set( 'settings', $settings );
		$subscription->save();

		// Start import twice.
		Jeero\Inbox\pickup_items();
		Jeero\Inbox\pickup_items();

		$args = array(
			'status' => array( 'draft' ),
		);
		
		$events = $this->get_events( $args );
		
		$actual = count( $events );
		$expected = 1;
		$this->assertEquals( $expected, $actual );
		
		$actual = $events[ 0 ]->post_title;
		$expected = 'A test event';
		$this->assertEquals( $expected, $actual );
		
	}


}