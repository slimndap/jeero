<?php
/**
 * @group inbox
 */
 
use Jeero\Mother;
use Jeero\Inbox;
use Jeero\Subscriptions;

class Inbox_Test extends Jeero_Test {

	function test_get_inbox() {
		
		add_filter( 'jeero/mother/get/response/endpoint=inbox', array( $this, 'get_mock_response_for_get_inbox' ), 10, 3 );
		
		$actual = Inbox\pickup_items();
		$expected = true;
		
		$this->assertEquals( $expected, $actual );
		
	}

	function test_next_pickup_is_scheduled() {

		add_filter( 'jeero/mother/get/response/endpoint=inbox', array( $this, 'get_mock_response_for_get_inbox' ), 10, 3 );

		$now = time();
		
		wp_schedule_single_event( $now, Inbox\PICKUP_ITEMS_HOOK );

		// Get items from inbox to add some subscriptions to the DB.
		$items = Inbox\pickup_items();
		
		$actual = wp_next_scheduled( Inbox\PICKUP_ITEMS_HOOK );
		$expected = $now;
		
		$this->assertGreaterThan( $expected, $actual );

		
	}
	
}
