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
	
	function get_events( $args = array() ) {
		
		$calendar = Jeero\Calendars\get_calendar( $this->calendar );
		
		$defaults = array(
			'post_type' => $calendar->get_post_type(),
			'meta_query' => array(
				array(
					'key' => 'jeero/'.$this->calendar.'/veezi/ref',
					'value' => 'e123',					
				),
			),
		);
		
		$args = wp_parse_args( $args, $defaults );

		return get_posts( $args );
		
	}
	
	function test_inbox_event_is_soldout() {

		global $wp_theatre;
		
		add_filter( 
			'jeero/mother/get/response/endpoint=subscriptions/a fake ID', 
			array( $this, 'get_mock_response_for_get_subscription' ), 
			10, 3 
		);

		$jeero_test = $this;

		add_filter( 'jeero/mother/post/response/endpoint=inbox/big', function( $response, $endpoint, $args ) use ( $jeero_test ) {
		
			$inbox = $jeero_test->get_mock_response_for_get_inbox( $response, $endpoint, $args );
			
			$body = json_decode( $inbox[ 'body' ] );
			$body[ 0 ]->data->status = 'soldout';
			$inbox[ 'body' ] = json_encode( $body );

			return $inbox;
			
		}, 10, 3 );
		
		$subscription = Jeero\Subscriptions\get_subscription( 'a fake ID' );
		
		$settings = array(
			'theater' => 'veezi',
			'calendar' => array( $this->calendar ),
		);
		
		$subscription->set( 'settings', $settings );
		$subscription->save();

		Jeero\Inbox\pickup_items();
		Jeero\Inbox\pickup_items();



		$args = array(
			'status' => array( 'draft' ),
		);
		$events = $wp_theatre->events->get( $args );

		$actual = $events[ 0 ]->tickets_status();
		$expected = '_soldout';
		$this->assertEquals( $expected, $actual );			
	}
	
	
	/**
	 * Test if prices with a title and trailing zeros don't disappear after every other import.
	 * @see https://github.com/slimndap/jeero/issues/6
	 * 
	 * @since	0.15.4
	 */
	function test_prices_dont_disappear() {

		global $wp_theatre;
		
		$this->import_event();

		$args = array(
			'status' => array( 'draft' ),
		);
		$events = $wp_theatre->events->get( $args );
		
		$actual = $events[ 0 ]->prices();
		$expected = 2;
		$this->assertCount( $expected, $actual );
		
		Inbox\pickup_items();

		$args = array(
			'status' => array( 'draft' ),
		);
		
		$args = array(
			'status' => array( 'draft' ),
		);
		$events = $wp_theatre->events->get( $args );
		
		$actual = $events[ 0 ]->prices();
		$expected = 2;
		$this->assertCount( $expected, $actual );
		
			
	}

}