<?php
/**
 * @group inbox
 */
 
use Jeero\Mother;
use Jeero\Inbox;
use Jeero\Subscriptions;

class Inbox_Test extends WP_UnitTestCase {

	function setUp() {
		parent::setUp();
	}
    
	function tearDown() {
		parent::tearDown();
	}    	
	function get_mock_response_for_get_inbox( $response, $endpoint, $args ) {
		
		if ( 'inbox' != $endpoint ) {
			return $response;
		}
		
		$body = array(
			array(
				'ID' => 'a fake inbox ID',
				'action' => 'import',
				'item' => 'event',
				'theater' => 'veezi',
				'data' => array(
					'title' => 'A test event',
					'description' => 'A description.',
					'startDate' => time() + 48 * HOUR_IN_SECONDS,
					'endDate' => time() + 90 * MINUTE_IN_SECONDS + 48 * HOUR_IN_SECONDS,
					'image' => '',
					'location' => array(
						'name' => 'Paard',
						'address' => array(
							
						),
					),
					'offers' => array(
						array(
							'availability' => 'InStock',
							'price' => '20',
							'currency' => 'EUR',
							'url' => 'https://slimndap.com',	
						),
					),	
				
				),
				'raw' => 'Raw event data',
			),
		);
		
		return array(
			'body' => json_encode( $body ),
			'response' => array(
				'code' => 200,
				'message' => 'OK',	
			),
		);
			
	}

	function test_get_inbox() {
		
		add_filter( 'jeero/mother/get/response', array( $this, 'get_mock_response_for_get_inbox' ), 10, 3 );
		$items = Inbox\pickup_items();
		
		$actual = count( $items );
		$expected = 1;
		
		$this->assertEquals( $expected, $actual );
		
	}

	function test_next_pickup_is_scheduled() {

		$now = time();
		
		wp_schedule_single_event( $now, Inbox\PICKUP_ITEMS_HOOK );

		// Get items from inbox to add some subscriptions to the DB.
		$items = Inbox\pickup_items();
		
		$actual = wp_next_scheduled( Inbox\PICKUP_ITEMS_HOOK );
		$expected = $now;
		
		$this->assertGreaterThan( $expected, $actual );

		
	}
	
	function test_next_pickup_is_triggered() {
		add_filter( 'jeero/mother/get/response', array( $this, 'get_mock_response_for_get_inbox' ), 10, 3 );

		do_action( Inbox\PICKUP_ITEMS_HOOK );
	}
	


}
