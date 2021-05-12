<?php
use Jeero\Subscriptions;
use Jeero\Subscriptions\Subscription;
use Jeero\Admin;
use Jeero\Inbox;

class All_In_One_Event_Calendar_Test extends Jeero_Test {
	
	function test_plugin_activated() {
		
		$actual = class_exists( 'Ai1ec_Front_Controller' );
		$expected = true;
		
		$this->assertEquals( $expected, $actual );
		
	}
	
	function test_calendar_in_subscription_edit_form() {

		add_filter( 'jeero/mother/get/response/endpoint=subscriptions/a fake ID', array( $this, 'get_mock_response_for_get_subscription' ), 10, 3 );

		$_GET[ 'edit' ] = 'a fake ID';
		
		$actual = Admin\Subscriptions\get_admin_page_html();
		$expected = '<input name="calendar[]" type="checkbox" value="All_In_One_Event_Calendar">';
		
		$this->assertContains( $expected, $actual );
		
	}
	
	function test_inbox_event_is_imported() {
		
		add_filter( 
			'jeero/mother/get/response/endpoint=subscriptions/a fake ID', 
			array( $this, 'get_mock_response_for_get_subscription' ), 
			10, 3 
		);

		add_filter( 'jeero/mother/get/response/endpoint=inbox', array( $this, 'get_mock_response_for_get_inbox' ), 10, 3 );
		
		$subscription = Jeero\Subscriptions\get_subscription( 'a fake ID' );
		
		$settings = array(
			'theater' => 'veezi',
			'calendar' => array( 'All_In_One_Event_Calendar' ),
		);
		
		$subscription->set( 'settings', $settings );
		$subscription->save();

		Inbox\pickup_items();

		$args = array(
			'post_type' => \AI1EC_POST_TYPE,
			'post_status' => 'any',
			'meta_query' => array(
				array(
					'key' => 'jeero/all_in_one_event_calendar/ref',
					'value' => 123,					
				),
			),
		);
		
		$events = get_posts( $args );

		$actual = count( $events );
		$expected = 1;
		$this->assertEquals( $expected, $actual );
		
		$actual = $events[ 0 ]->post_title;
		$expected = 'A test event';
		$this->assertEquals( $expected, $actual );
		
	}
}