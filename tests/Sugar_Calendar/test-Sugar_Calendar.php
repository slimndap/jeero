<?php
use Jeero\Subscriptions;
use Jeero\Subscriptions\Subscription;
use Jeero\Admin;
use Jeero\Inbox;

class Sugar_Calendar_Test extends Jeero_Test {
	
	function test_plugin_activated() {
		
		$actual = class_exists( 'Sugar_Calendar_Requirements_Check' );
		$expected = true;
		
		$this->assertEquals( $expected, $actual );
		
	}
	
	function test_calendar_in_subscription_edit_form() {
		add_filter( 'jeero/mother/get/response/endpoint=subscriptions/a fake ID', array( $this, 'get_mock_response_for_get_subscription' ), 10, 3 );

		$_GET[ 'edit' ] = 'a fake ID';
		
		$actual = Admin\Subscriptions\get_admin_page_html();
		$expected = '<input name="calendar[]" type="checkbox" value="Sugar_Calendar">';
		
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
			'calendar' => array( 'Sugar_Calendar' ),
			'Sugar_Calendar/import/template/title' => '{{title}} with custom template',
			'Sugar_Calendar/import/template/content' => '{{description|raw}}{% if tickets_url %}<h3>Tickets</h3>{{tickets_url}}{% endif %}',
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
		$expected = 'A test event with custom template';
		$this->assertEquals( $expected, $actual );	
		
		$actual = $events[ 0 ]->post_content;
		$expected = '<p>A description.</p><h3>Tickets</h3>https://slimndap.com';
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
			'calendar' => array( 'Sugar_Calendar' ),
			'Sugar_Calendar/import/template/content' => '<h3>{{ subtitle }}</h3>{{description|raw}}',
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
			'calendar' => array( 'Sugar_Calendar' ),
			'Sugar_Calendar/import/template/title' => '{% if xxx}{{ title }}',
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
		$expected = 'Rendering template for Sugar Calendar field failed.';
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
			'calendar' => array( 'Sugar_Calendar' ),
			'Sugar_Calendar/import/template/custom_fields' => array(
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

		$actual = get_post_meta( $events[ 0 ]->ID, 'some custom field', true );
		$expected = 'Custom field for A test event';
		$this->assertEquals( $expected, $actual );	
			
	}

}