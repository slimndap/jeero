<?php
use Jeero\Subscriptions;
use Jeero\Subscriptions\Subscription;
use Jeero\Admin;
use Jeero\Inbox;

class Theater_For_WordPress_Test extends Jeero_Test {
	
	function test_plugin_activated() {
		
		$actual = class_exists( 'WP_Theatre' );
		$expected = true;
		
		$this->assertEquals( $expected, $actual );
		
	}
	
	function test_calendar_in_subscription_edit_form() {

		add_filter( 'jeero/mother/get/response/endpoint=subscriptions/a fake ID', array( $this, 'get_mock_response_for_get_subscription' ), 10, 3 );

		$_GET[ 'edit' ] = 'a fake ID';
		
		$actual = Admin\Subscriptions\get_admin_page_html();
		$expected = '<input name="calendar[]" type="checkbox" value="Theater_For_WordPress">';
		
		$this->assertContains( $expected, $actual );
		
	}
	
	function test_inbox_event_is_imported() {
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
			'calendar' => array( 'Theater_For_WordPress' ),
		);
		
		$subscription->set( 'settings', $settings );
		$subscription->save();

		Inbox\pickup_items();

		$args = array(
			'status' => array( 'draft' ),
		);
		
		$events = $wp_theatre->productions->get( $args );
		
		$actual = count( $events );
		$expected = 1;
		$this->assertEquals( $expected, $actual );
		
		$actual = $events[ 0 ]->title();
		$expected = 'A test event';
		$this->assertEquals( $expected, $actual );
		
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
			'calendar' => array( 'Theater_For_WordPress' ),
		);
		
		$subscription->set( 'settings', $settings );
		$subscription->save();

		// Start import twice.
		Inbox\pickup_items();
		Inbox\pickup_items();

		$args = array(
			'status' => array( 'draft' ),
		);
		
		$events = $wp_theatre->productions->get( $args );
		
		$actual = count( $events );
		$expected = 1;
		$this->assertEquals( $expected, $actual );
		
		$actual = $events[ 0 ]->title();
		$expected = 'A test event';
		$this->assertEquals( $expected, $actual );
		
	}

	/**
	 * Tests if custom calendar fields are present in the subscription form.
	 * 
	 * @since	1.4
	 */
	function test_edit_form_has_custom_fields() {

		add_filter( 'jeero/mother/get/response/endpoint=subscriptions/a fake ID', array( $this, 'get_mock_response_for_get_subscription' ), 10, 3 );

		$subscription = Subscriptions\get_subscription( 'a fake ID' );
		$settings = array(
			'calendar' => array( 'Theater_For_WordPress' ),
		);
		$subscription->set( 'settings', $settings );
		$subscription->save();

		$subscription = Subscriptions\get_subscription( 'a fake ID' );

		$actual = wp_list_pluck( $subscription->get( 'fields' ), 'name' );
		$expected = 'Theater_For_WordPress/import/update/title';
		
		$this->assertContains( $expected, $actual, print_r($actual, true ) );

	}
	
	/**
	 * Tests if the subscriptions form is prefilled with custom field setting.
	 * 
	 * @since	1.4
	 */
	function test_edit_form_has_field_value_for_custom_field() {

		add_filter( 'jeero/mother/get/response/endpoint=subscriptions/a fake ID', array( $this, 'get_mock_response_for_get_subscription' ), 10, 3 );

		$subscription = Subscriptions\get_subscription( 'a fake ID' );
		$settings = array(
			'calendar' => array( 'Theater_For_WordPress' ),
			'Theater_For_WordPress/import/update/title' => 'always',
		);
		$subscription->set( 'settings', $settings );
		$subscription->save();

		$_GET = array(
			'edit' => 'a fake ID',
		);
		
		$actual = Admin\Subscriptions\get_admin_page_html();
		$expected = '<option value="always" selected=\'selected\'';
		
		$this->assertContains( $expected, $actual );


	}
	
	/**
	 * Tests if title is overwritten after import.
	 * 
	 * @since	1.4
	 */
	function test_title_is_updated_after_second_import() {
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
			'calendar' => array( 'Theater_For_WordPress' ),
			'Theater_For_WordPress/import/update/title' => 'always',
		);
		
		$subscription->set( 'settings', $settings );
		$subscription->save();

		// Start first import.
		Inbox\pickup_items();

		// Update title of first event.
		$args = array(
			'status' => array( 'draft' ),
		);		
		$events = $wp_theatre->productions->get( $args );
		
		$args = array(
			'ID' => $events[ 0 ]->ID,
			'post_title' => 'A test event with a new title',
		);
		wp_update_post( $args );

		// Start second import.
		Inbox\pickup_items();

		$args = array(
			'status' => array( 'draft' ),
		);
		$events = $wp_theatre->productions->get( $args );
		
		$actual = $events[ 0 ]->title();
		$expected = 'A test event';
		$this->assertEquals( $expected, $actual );
		
	}
	
	/**
	 * Tests if title is not overwritten after import.
	 * 
	 * @since	1.4
	 */
	function test_title_is_not_updated_after_second_import() {
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
			'calendar' => array( 'Theater_For_WordPress' ),
		);
		
		$subscription->set( 'settings', $settings );
		$subscription->save();

		// Start first import.
		Inbox\pickup_items();

		// Update title of first event.
		$args = array(
			'status' => array( 'draft' ),
		);		
		$events = $wp_theatre->productions->get( $args );
		
		$args = array(
			'ID' => $events[ 0 ]->ID,
			'post_title' => 'A test event with a new title',
		);
		wp_update_post( $args );

		// Start second import.
		Inbox\pickup_items();

		$args = array(
			'status' => array( 'draft' ),
		);
		$events = $wp_theatre->productions->get( $args );
		
		$actual = $events[ 0 ]->title();
		$expected = 'A test event with a new title';
		$this->assertEquals( $expected, $actual );
		
	}

	/**
	 * Tests if event is published after first import.
	 * 
	 * @since	1.4
	 */
	function test_inbox_event_is_published() {
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
			'calendar' => array( 'Theater_For_WordPress' ),
			'Theater_For_WordPress/import/status' => 'publish',
		);
		
		$subscription->set( 'settings', $settings );
		$subscription->save();

		// Start import.
		Inbox\pickup_items();

		$events = $wp_theatre->productions->get( );
		
		$actual = count( $events );
		$expected = 1;
		$this->assertEquals( $expected, $actual );
		
	}
	
	/**
	 * Tests if the import of an event is skipped if a WP_Error is returned in one of the previous steps.
	 * 
	 * @since	1.5
	 */
	function test_inbox_event_is_not_imported_on_error() {
		global $wp_theatre;
		
		add_filter( 
			'jeero/mother/get/response/endpoint=subscriptions/a fake ID', 
			array( $this, 'get_mock_response_for_get_subscription' ), 
			10, 3 
		);

		add_filter( 'jeero/mother/get/response/endpoint=inbox', array( $this, 'get_mock_response_for_get_inbox' ), 10, 3 );
		
		$subscription = Jeero\Subscriptions\get_subscription( 'a fake ID' );
		
		$theater = 'veezi';
		$calendar = 'Theater_For_WordPress';
		
		$settings = array(
			'theater' => $theater,
			'calendar' => array( $calendar ),
		);
		
		$subscription->set( 'settings', $settings );
		$subscription->save();

		$args = array(
			'status' => array( 'draft' ),
		);
		
		// Test if the regular import still works.
		Inbox\pickup_items();
		$actual = $wp_theatre->productions->get( $args );
		$expected = 1;
		$this->assertCount( $expected, $actual );
		
		// Delete the improted event.
		wp_delete_post( $actual[ 0 ]->ID, true );
		
		// Return a WP_Error just before events are imported.
		add_filter( 'jeero/inbox/process/item/import/calendar='.$calendar, function() {
			return new WP_Error( 'error', 'A random error' );
		}, 9 );
		
		// Test if the import is skipped.
		Inbox\pickup_items();
		$actual = $wp_theatre->productions->get( $args );
		$expected = 0;
		$this->assertCount( $expected, $actual );
		
	}

	/**
	 * Tests if the categories are imported as class types.
	 * 
	 * @since	1.6
	 */
	function test_class_types_are_imported() {
		
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
			'calendar' => array( 'Theater_For_WordPress' ),
		);
		
		$subscription->set( 'settings', $settings );
		$subscription->save();

		Inbox\pickup_items();

		$args = array(
			'status' => array( 'draft' ),
		);

		$events = $wp_theatre->productions->get( $args );
		
		$actual = $events[ 0 ]->categories();
		$expected = 2;
		$this->assertCount( $expected, $actual );

		$actual = $events[ 0 ]->categories( array( 'html' => true ));
		$expected = 'Category A';
		$this->assertContains( $expected, $actual );		
	}


}