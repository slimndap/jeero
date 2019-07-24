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
			'schedule' => array(
				'a fake ID' => array(
					'next_delivery' => strtotime( 'Tomorrow 8PM' ),
				),
				'another fake ID' => array(
					'next_delivery' => strtotime( 'Tomorrow 10PM' ),
				),
			),
			'items' => array(
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

	function get_mock_response_for_get_subscriptions( $response, $endpoint, $args ) {
		
		if ( 'subscriptions' != $endpoint ) {
			return $response;
		}
		
		$body = array(
			array(
				'ID' => 'a fake ID',
				'status' => 'setup',
				'fields' => array(
					array(
						'name' => 'theater',
						'type' => 'select',
						'label' => 'Theater',
						'required' => true,
						'choices' => array(
							'veezi' => 'Veezi',
							'seatgeek' => 'Seatgeek',
							'stager' => 'Stager',	
						),
					),
				),
			),			
			array(
				'ID' => 'another fake ID',
				'status' => 'active',
				'next_update' => time() + DAY_IN_SECONDS,
				'fields' => array(
					array(
						'name' => 'theater',
						'type' => 'select',
						'label' => 'Theater',
						'required' => true,
						'choices' => array(
							'veezi' => 'Veezi',
							'seatgeek' => 'Seatgeek',
							'stager' => 'Stager',	
						),
					),
				),
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
	
	function get_mock_response_for_get_subscription( $response, $endpoint, $args ) {
		
		if ( strpos( $endpoint, 'subscriptions/' !== 0 ) ) {
			return $response;
		}
		
		$body = array(
			'ID' => 'a fake ID',
			'status' => 'setup',
			'fields' => array(
				array(
					'name' => 'theater',
					'type' => 'select',
					'label' => 'Theater',
					'required' => true,
					'choices' => array(
						'veezi' => 'Veezi',
						'seatgeek' => 'Seatgeek',
						'stager' => 'Stager',	
					),
				),
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

	function test_get_next_delivery() {
		
		add_filter( 'jeero/mother/get/response', array( $this, 'get_mock_response_for_get_inbox' ), 10, 3 );

		// Get items from inbox to add some subscriptions to the DB.
		$items = Inbox\pickup_items();
		
		$actual = Inbox\get_next_delivery();
		$expected = strtotime( 'Tomorrow 8PM' );
		
		$this->assertEquals( $expected, $actual );
		
	}
	
	function test_next_pickup_is_scheduled() {

		add_filter( 'jeero/mother/get/response', array( $this, 'get_mock_response_for_get_inbox' ), 10, 3 );

		// Get items from inbox to add some subscriptions to the DB.
		$items = Inbox\pickup_items();
		
		$actual = wp_next_scheduled( Inbox\PICKUP_ITEMS_HOOK );
		$expected = strtotime( 'Tomorrow 8PM' );
		
		$this->assertEquals( $expected, $actual );

		
	}
	
	function test_next_delivery_is_triggered() {
		add_filter( 'jeero/mother/get/response', array( $this, 'get_mock_response_for_get_inbox' ), 10, 3 );

		do_action( 'Inbox\PICKUP_ITEMS_HOOK' );
	}
	


}
