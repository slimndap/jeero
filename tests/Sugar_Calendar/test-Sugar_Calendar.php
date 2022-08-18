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
	
	protected function setUp(): void {	
		parent::setUp();
		sugar_calendar_register_meta_data();
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
		$this->assertTrue( true );
		
	}

	function test_has_correct_times() {
		
		// Set website time zone to non-UTC.
		update_option( 'gmt_offset', +2 );
		
		$this->import_event();

		$args = array(
			'post_status' => 'draft',
		);
		$events = $this->get_events( $args );

		$actual = date( 'H:i', strtotime( sugar_calendar_get_event_by_object( $events[ 0 ]->ID, 'post' )->start ) );
		$expected = date( 'H:i', current_time( 'timestamp' ) + 48 * HOUR_IN_SECONDS );
		$this->assertEquals( $expected, $actual );
		
		$actual = date( 'H:i', strtotime( sugar_calendar_get_event_by_object( $events[ 0 ]->ID, 'post' )->end ) );
		$expected = date( 'H:i', current_time( 'timestamp' ) + 90 * MINUTE_IN_SECONDS + 48 * HOUR_IN_SECONDS );
		$this->assertEquals( $expected, $actual );
		
	}
	
	function test_has_location() {

		$this->import_event( );

		$args = array(
			'post_status' => 'draft',
		);
		$events = $this->get_events( $args );

		$event = sugar_calendar_get_event_by_object( $events[ 0 ]->ID, 'post' );

		$actual = get_event_meta( $event->id, 'location', true );
		$expected = 'Paard, Den Haag';
		$this->assertEquals( $expected, $actual );
		
	}

	function test_in_calendar() {

		wp_create_term( 'Jeero Calendar', \sugar_calendar_get_calendar_taxonomy_id() );

		$settings = array(
			$this->calendar.'/import/sc_calendar' => array( 'jeero-calendar' ),
		);

		$this->import_event( $settings );

		$args = array(
			'post_status' => 'draft',
		);
		$events = $this->get_events( $args );
		
		$actual = \wp_list_pluck( \wp_get_object_terms( $events[ 0 ]->ID, \sugar_calendar_get_calendar_taxonomy_id() ), 'name' );
		$expected = 'Jeero Calendar';
		$this->assertContains( $expected, $actual );
		
	}

}