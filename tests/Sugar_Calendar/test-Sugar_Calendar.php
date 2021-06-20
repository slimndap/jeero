<?php
use Jeero\Subscriptions;
use Jeero\Subscriptions\Subscription;
use Jeero\Admin;
use Jeero\Inbox;

class Sugar_Calendar_Test extends Post_Based_Calendar_Test {
	
	function __construct() {
		
		parent::__construct();
		
		$this->calendar = 'Sugar_Calendar';
		
	}
	
	
	function test_inbox_event_uses_default_templates() {

		add_filter( 
			'jeero/mother/get/response/endpoint=subscriptions/a fake ID', 
			array( $this, 'get_mock_response_for_get_subscription' ), 
			10, 3 
		);

		add_filter( 'jeero/mother/get/response/endpoint=inbox', array( $this, 'get_mock_response_for_get_inbox' ), 10, 3 );
		
		$subscription = Jeero\Subscriptions\get_subscription( 'a fake ID' );
		
		$settings = array(
			'theater' => 'veezi',
			'calendar' => array( 'Sugar_Calendar' ),
		);
		
		$subscription->set( 'settings', $settings );
		$subscription->save();

		Inbox\pickup_items();

		$args = array(
			'post_type' => \sugar_calendar_get_event_post_type_id(),
			'post_status' => 'any',
			'meta_query' => array(
				array(
					'key' => 'jeero/sugar_calendar/veezi/ref',
					'value' => 123,					
				),
			),
		);
		
		$events = \get_posts( $args );

		$actual = $events[ 0 ]->post_title;
		$expected = 'A test event';
		$this->assertEquals( $expected, $actual );	
		
		$actual = $events[ 0 ]->post_content;
		$expected = "<p>A description.</p>\n\n<a href=\"https://slimndap.com\">Tickets</a>\n";
		$this->assertEquals( $expected, $actual );	
		
	}
	
	function test_categories_are_imported() {
		
		// Skip test, Sugar Calendar does not support categories.
		
	}

}