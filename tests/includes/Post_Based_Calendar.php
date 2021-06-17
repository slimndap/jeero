<?php
use Jeero\Admin;

class Post_Based_Calendar_Test extends Jeero_Test {
	
	function __construct() {
		
		parent::__construct();
		
		$this->calendar = '';
		
	}
	
	function get_events( $args = array() ) {
		
		$calendar = Jeero\Calendars\get_calendar( $this->calendar );
		
		$defaults = array(
			'post_type' => $calendar->get_post_type(),
			'meta_query' => array(
				array(
					'key' => 'jeero/'.$this->calendar.'/veezi/ref',
					'value' => 123,					
				),
			),
		);
		
		$args = wp_parse_args( $args, $defaults );

		return get_posts( $args );
		
	}
	
	function import_event( $settings = array() ) {
		
		$defaults = array(
			'theater' => 'veezi',
			'calendar' => array( $this->calendar ),			
		);
		
		$settings = wp_parse_args( $settings, $defaults );
		
		add_filter( 
			'jeero/mother/get/response/endpoint=subscriptions/a fake ID', 
			array( $this, 'get_mock_response_for_get_subscription' ), 
			10, 3 
		);

		add_filter( 'jeero/mother/get/response/endpoint=inbox', array( $this, 'get_mock_response_for_get_inbox' ), 10, 3 );
		
		$subscription = Jeero\Subscriptions\get_subscription( 'a fake ID' );
		$calendar = Jeero\Calendars\get_calendar( $this->calendar );
		
		$subscription->set( 'settings', $settings );
		$subscription->save();

		Jeero\Inbox\pickup_items();
		
		
	}
	

	function test_plugin_activated() {
		
		$calendar = Jeero\Calendars\get_calendar( $this->calendar );
		
		$actual = $calendar->is_active();
		$this->assertTrue( $actual );
		
	}
	
	function test_calendar_in_subscription_edit_form() {
		add_filter( 'jeero/mother/get/response/endpoint=subscriptions/a fake ID', array( $this, 'get_mock_response_for_get_subscription' ), 10, 3 );

		$_GET[ 'edit' ] = 'a fake ID';
		
		$actual = Admin\Subscriptions\get_admin_page_html();
		$expected = '<input name="calendar[]" type="checkbox" value="'.$this->calendar. '">';
		
		$this->assertContains( $expected, $actual );
		
	}
		
	function test_inbox_event_is_imported() {
		
		$this->import_event();
		
		$args = array(
			'post_status' => 'any',
		);		
		$events = $this->get_events( $args );
		
		$actual = count( $events );
		$expected = 1;
		$this->assertEquals( $expected, $actual );
		
		$actual = $events[ 0 ]->post_title;
		$expected = 'A test event';
		$this->assertEquals( $expected, $actual );
		
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
			'calendar' => array( $this->calendar ),
		);
		
		$subscription->set( 'settings', $settings );
		$subscription->save();

		Jeero\Inbox\pickup_items();

		$args = array(
			'post_status' => 'any',
		);
		
		$events = $this->get_events( $args );

		$actual = $events[ 0 ]->post_title;
		$expected = 'A test event';
		$this->assertEquals( $expected, $actual );	
		
		$actual = $events[ 0 ]->post_content;
		$expected = '<p>A description.</p>';
		$this->assertEquals( $expected, $actual );	
			
	}

	function test_inbox_event_uses_custom_templates() {
		
		add_filter( 
			'jeero/mother/get/response/endpoint=subscriptions/a fake ID', 
			array( $this, 'get_mock_response_for_get_subscription' ), 
			10, 3 
		);

		add_filter( 'jeero/mother/get/response/endpoint=inbox', array( $this, 'get_mock_response_for_get_inbox' ), 10, 3 );
		
		$subscription = Jeero\Subscriptions\get_subscription( 'a fake ID' );
		
		$settings = array(
			'theater' => 'veezi',
			'calendar' => array( $this->calendar ),
			$this->calendar.'/import/post_fields' => array(
				'title' => array(
					'template' => '{{title}} with custom template',
				),
				'content' => array(
					'template' => '{{description|raw}}{% if tickets_url %}<h3>Tickets</h3>{{tickets_url}}{% endif %}',
				),
				'excerpt' => array(
					'template' => '{{ description|striptags|slice(0, 5) ~ \'...\'}}',
				),
			),
		);
		
		$subscription->set( 'settings', $settings );
		$subscription->save();

		Jeero\Inbox\pickup_items();

		$args = array(
			'post_status' => 'any',
		);
		
		$events = $this->get_events( $args );

		$actual = $events[ 0 ]->post_title;
		$expected = 'A test event with custom template';
		$this->assertEquals( $expected, $actual );	
		
		$actual = $events[ 0 ]->post_content;
		$expected = '<p>A description.</p><h3>Tickets</h3>https://slimndap.com';
		$this->assertEquals( $expected, $actual );	
			
		$actual = $events[ 0 ]->post_excerpt;
		$expected = 'A des...';
		$this->assertEquals( $expected, $actual );	
			
	}
		
	function test_inbox_event_uses_custom_template_fields() {
		
		add_filter( 
			'jeero/mother/get/response/endpoint=subscriptions/a fake ID', 
			array( $this, 'get_mock_response_for_get_subscription' ), 
			10, 3 
		);

		add_filter( 'jeero/mother/get/response/endpoint=inbox', array( $this, 'get_mock_response_for_get_inbox' ), 10, 3 );
		
		$subscription = Jeero\Subscriptions\get_subscription( 'a fake ID' );
		
		$settings = array(
			'theater' => 'veezi',
			'calendar' => array( $this->calendar ),
			$this->calendar.'/import/post_fields' => array(
				'content' => array(
					'template' => '<h3>{{ subtitle }}</h3>{{description|raw}}',
				),
			),
		);
		
		$subscription->set( 'settings', $settings );
		$subscription->save();

		Jeero\Inbox\pickup_items();

		$args = array(
			'post_status' => 'any',
		);
		
		$events = $this->get_events( $args );

		$actual = $events[ 0 ]->post_content;
		$expected = '<h3>The subtitle</h3><p>A description.</p>';
		$this->assertEquals( $expected, $actual );	
			
	}
	
	function test_inbox_event_silently_fails_incorrect_templates() {
		
		add_filter( 
			'jeero/mother/get/response/endpoint=subscriptions/a fake ID', 
			array( $this, 'get_mock_response_for_get_subscription' ), 
			10, 3 
		);

		add_filter( 'jeero/mother/get/response/endpoint=inbox', array( $this, 'get_mock_response_for_get_inbox' ), 10, 3 );
		
		$subscription = Jeero\Subscriptions\get_subscription( 'a fake ID' );
		
		$settings = array(
			'theater' => 'veezi',
			'calendar' => array( $this->calendar ),
			$this->calendar.'/import/post_fields' => array(
				'title' => array(
					'template' => '{% if xxx}{{ title }}',
				),
			),
		);
		
		$subscription->set( 'settings', $settings );
		$subscription->save();

		Jeero\Inbox\pickup_items();

		$args = array(
			'post_status' => 'any',
		);
		
		$events = $this->get_events( $args );

		$actual = $events[ 0 ]->post_title;
		$expected = 'Rendering title template failed:';
		$this->assertContains( $expected, $actual );	
			
	}

	function test_inbox_event_imports_custom_fields() {
		
		add_filter( 
			'jeero/mother/get/response/endpoint=subscriptions/a fake ID', 
			array( $this, 'get_mock_response_for_get_subscription' ), 
			10, 3 
		);

		add_filter( 'jeero/mother/get/response/endpoint=inbox', array( $this, 'get_mock_response_for_get_inbox' ), 10, 3 );
		
		$subscription = Jeero\Subscriptions\get_subscription( 'a fake ID' );
		
		$settings = array(
			'theater' => 'veezi',
			'calendar' => array( $this->calendar ),
			$this->calendar.'/import/template/custom_fields' => array(
				array(
					'name' => 'some custom field',
					'template' => 'Custom field for {{title}}',
				),
			),
		);
		
		$subscription->set( 'settings', $settings );
		$subscription->save();

		Jeero\Inbox\pickup_items();

		$args = array(
			'post_status' => 'any',
		);
		
		$events = $this->get_events( $args );

		$actual = get_post_meta( $events[ 0 ]->ID, 'some custom field', true );
		$expected = 'Custom field for A test event';
		$this->assertEquals( $expected, $actual );	
			
	}

	/**
	 * Tests if custom calendar fields are present in the subscription form.
	 * 
	 * @since	1.4
	 */
	function test_edit_form_has_custom_fields() {

		add_filter( 'jeero/mother/get/response/endpoint=subscriptions/a fake ID', array( $this, 'get_mock_response_for_get_subscription' ), 10, 3 );

		$subscription = Jeero\Subscriptions\get_subscription( 'a fake ID' );
		$settings = array(
			'calendar' => array( $this->calendar ),
		);
		$subscription->set( 'settings', $settings );
		$subscription->save();

		$subscription = Jeero\Subscriptions\get_subscription( 'a fake ID' );

		$actual = wp_list_pluck( $subscription->get( 'fields' ), 'name' );
		$expected = $this->calendar.'/import/post_fields';
		
		$this->assertContains( $expected, $actual, print_r($actual, true ) );

	}

	/**
	 * Tests if the subscriptions form is prefilled with custom field setting.
	 * 
	 * @since	1.4
	 */
	function test_edit_form_has_field_value_for_custom_field() {

		add_filter( 'jeero/mother/get/response/endpoint=subscriptions/a fake ID', array( $this, 'get_mock_response_for_get_subscription' ), 10, 3 );

		$subscription = Jeero\Subscriptions\get_subscription( 'a fake ID' );
		$settings = array(
			'calendar' => array( $this->calendar ),
			$this->calendar.'/import/post_fields' => array( 
				'title' => array( 
					'update' => 'always',
				),
			),
		);
		$subscription->set( 'settings', $settings );
		$subscription->save();

		$_GET = array(
			'edit' => 'a fake ID',
		);
		
		$actual = Jeero\Admin\Subscriptions\get_admin_page_html();
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
			'calendar' => array( $this->calendar ),
			$this->calendar.'/import/post_fields' => array(
				'title' => array(
					'update' => 'always',
				),
			),
		);
		
		$subscription->set( 'settings', $settings );
		$subscription->save();

		// Start first import.
		Jeero\Inbox\pickup_items();

		// Update title of first event.
		$args = array(
			'post_status' => array( 'draft' ),
		);		
		$events = $this->get_events( $args );
		
		$args = array(
			'ID' => $events[ 0 ]->ID,
			'post_title' => 'A test event with a new title',
		);
		wp_update_post( $args );

		// Start second import.
		Jeero\Inbox\pickup_items();

		$args = array(
			'post_status' => array( 'draft' ),
		);
		$events = $this->get_events( $args );
		
		$actual = $events[ 0 ]->post_title;
		$expected = 'A test event';
		$this->assertEquals( $expected, $actual );
		
	}
	
	function test_excerpt_is_updated_after_second_import() {
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
			'calendar' => array( $this->calendar ),
			$this->calendar.'/import/post_fields' => array(
				'excerpt' => array(
					'update' => 'always',
					'template' => '{{ description|striptags|slice(0, 5) ~ \'...\'}}',
				),
			),
		);
		
		$subscription->set( 'settings', $settings );
		$subscription->save();

		// Start first import.
		Jeero\Inbox\pickup_items();

		// Update title of first event.
		$args = array(
			'post_status' => array( 'draft' ),
		);		
		$events = $this->get_events( $args );
		
		$actual = $events[ 0 ]->post_excerpt;
		$expected = 'A des...';
		$this->assertEquals( $expected, $actual );

		$args = array(
			'ID' => $events[ 0 ]->ID,
			'post_excerpt' => 'A nice excerpt',
		);
		wp_update_post( $args );

		$args = array(
			'post_status' => array( 'draft' ),
		);		
		$events = $this->get_events( $args );
		
		$actual = $events[ 0 ]->post_excerpt;
		$expected = 'A nice excerpt';
		$this->assertEquals( $expected, $actual );

		// Start second import.
		Jeero\Inbox\pickup_items();

		$args = array(
			'post_status' => array( 'draft' ),
		);
		$events = $this->get_events( $args );
		
		$actual = $events[ 0 ]->post_excerpt;
		$expected = 'A des...';
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
			'calendar' => array( $this->calendar ),
		);
		
		$subscription->set( 'settings', $settings );
		$subscription->save();

		// Start import twice.
		Jeero\Inbox\pickup_items();
		Jeero\Inbox\pickup_items();

		$args = array(
			'post_status' => array( 'draft' ),
		);
		
		$events = $this->get_events( $args );
		
		$actual = count( $events );
		$expected = 1;
		$this->assertEquals( $expected, $actual );
		
		$actual = $events[ 0 ]->post_title;
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
		$calendar = Jeero\Calendars\get_calendar( $this->calendar );
		
		$settings = array(
			'theater' => 'veezi',
			'calendar' => array( $this->calendar ),
		);
		
		$subscription->set( 'settings', $settings );
		$subscription->save();

		// Start first import.
		Jeero\Inbox\pickup_items();

		// Update title of first event.
		$args = array(
			'post_status' => array( 'draft' ),
		);		
		$events = $this->get_events( $args );
		
		$actual = $events[ 0 ]->post_title;
		$expected = 'A test event';
		$this->assertEquals( $expected, $actual );

		$args = array(
			'ID' => $events[ 0 ]->ID,
			'post_title' => 'A test event with a new title',
		);
		wp_update_post( $args );

		$args = array(
			'post_status' => array( 'draft' ),
		);		
		$events = $this->get_events( $args );
		
		$actual = $events[ 0 ]->post_title;
		$expected = 'A test event with a new title';
		$this->assertEquals( $expected, $actual );

		// Start second import.
		Jeero\Inbox\pickup_items();

		$args = array(
			'post_status' => array( 'draft' ),
		);
		$events = $this->get_events( $args );
		
		$actual = $events[ 0 ]->post_title;
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
			'calendar' => array( $this->calendar ),
			$this->calendar.'/import/status' => 'publish',
		);
		
		$subscription->set( 'settings', $settings );
		$subscription->save();

		// Start import.
		Jeero\Inbox\pickup_items();

		$events = $this->get_events();
		
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
		
		$settings = array(
			'theater' => $theater,
			'calendar' => array( $this->calendar ),
		);
		
		$subscription->set( 'settings', $settings );
		$subscription->save();

		$args = array(
			'post_status' => array( 'draft' ),
		);
		
		// Test if the regular import still works.
		Jeero\Inbox\pickup_items();
		$actual = $this->get_events( $args );
		$expected = 1;
		$this->assertCount( $expected, $actual );
		
		// Delete the improted event.
		wp_delete_post( $actual[ 0 ]->ID, true );
		
		// Return a WP_Error just before events are imported.
		add_filter( 'jeero/inbox/process/item/import/calendar='.$this->calendar, function() {
			return new WP_Error( 'error', 'A random error' );
		}, 9 );
		
		// Test if the import is skipped.
		Jeero\Inbox\pickup_items();
		$actual = $this->get_events( $args );
		$expected = 0;
		$this->assertCount( $expected, $actual );
		
	}

	/**
	 * Tests if the categories are imported as class types.
	 * 
	 * @since	1.6
	 */
	function test_categories_are_imported() {
		
		global $wp_theatre;
		
		add_filter( 
			'jeero/mother/get/response/endpoint=subscriptions/a fake ID', 
			array( $this, 'get_mock_response_for_get_subscription' ), 
			10, 3 
		);

		add_filter( 'jeero/mother/get/response/endpoint=inbox', array( $this, 'get_mock_response_for_get_inbox' ), 10, 3 );
		
		$subscription = Jeero\Subscriptions\get_subscription( 'a fake ID' );
		$calendar = Jeero\Calendars\get_calendar( $this->calendar );
		
		if ( empty( $calendar->get_categories_taxonomy( $subscription ) ) ) {
			return;
		}
		
		$settings = array(
			'theater' => 'veezi',
			'calendar' => array( $this->calendar ),
		);
		
		$subscription->set( 'settings', $settings );
		$subscription->save();

		jeero\Inbox\pickup_items();

		$args = array(
			'post_status' => array( 'draft' ),
		);

		$events = $this->get_events( $args );

		$actual = wp_get_post_terms( $events[ 0 ]->ID, $calendar->get_categories_taxonomy( $subscription ) );

		$expected = 2;
		$this->assertCount( $expected, $actual );

	}
			
}
