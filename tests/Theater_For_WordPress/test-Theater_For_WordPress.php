<?php
use Jeero\Subscriptions;
use Jeero\Subscriptions\Subscription;
use Jeero\Admin;
use Jeero\Inbox;

class Theater_For_WordPress_Test extends Post_Based_Calendar_Test {
	
	function __construct() {
		
		parent::__construct();
		
		$this->calendar = 'Theater_For_WordPress';
		
	}
	
	function test_inbox_event_is_soldout() {

		global $wp_theatre;
		
		add_filter( 
			'jeero/mother/get/response/endpoint=subscriptions/a fake ID', 
			array( $this, 'get_mock_response_for_get_subscription' ), 
			10, 3 
		);

		$jeero_test = $this;

		add_filter( 'jeero/mother/get/response/endpoint=inbox', function( $response, $endpoint, $args ) use ( $jeero_test ) {
		
			$inbox = $jeero_test->get_mock_response_for_get_inbox( $response, $endpoint, $args );
			
			$body = json_decode( $inbox[ 'body' ] );
			$body[ 0 ]->data->status = 'soldout';
			$inbox[ 'body' ] = json_encode( $body );
			
			return $inbox;
			
		}, 10, 3 );
		
		$subscription = Jeero\Subscriptions\get_subscription( 'a fake ID' );
		
		$settings = array(
			'theater' => 'veezi',
			'calendar' => array( 'Theater_For_WordPress' ),
			'Theater_For_WordPress/import/template/custom_fields' => array(
				array(
					'name' => 'some custom field',
					'template' => 'Custom field for {{title}}',
				),
			),
		);
		
		$subscription->set( 'settings', $settings );
		$subscription->save();

		Inbox\pickup_items();

		$args = array(
			'status' => array( 'draft' ),
		);
		$events = $wp_theatre->events->get( $args );

		$actual = $events[ 0 ]->tickets_status();
		$expected = '_soldout';
		$this->assertEquals( $expected, $actual );			
	}

}