<?php
use Jeero\Subscriptions;
use Jeero\Subscriptions\Subscription;
use Jeero\Admin;
use Jeero\Inbox;

class WP_Event_Manager_Test extends Post_Based_Calendar_Test {
	
	function __construct() {
		
		parent::__construct();
		
		$this->calendar = 'WP_Event_Manager';
		
	}

	// Default test doesn't work. Maybe WP Event Manager is registering 'event_listing_category' too late?
	function test_categories_are_imported() {

	}
	
	function test_has_location() {

		$this->import_event( );

		$args = array(
			'post_status' => 'draft',
		);
		$events = $this->get_events( $args );

		$actual = get_post_meta( $events[ 0 ]->ID, '_event_location', true );
		$expected = 'Paard, Den Haag';
		$this->assertEquals( $expected, $actual );
		
	}	
}