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

		$this->assertEquals( 'soldout', get_post_meta( $events[0]->ID, 'jeero/import/post/tickets_status', true ) );
		$this->assertEquals( 'https://slimndap.com', get_post_meta( $events[0]->ID, 'jeero/import/post/tickets_url', true ) );
		$this->assertEquals( 'veezi', get_post_meta( $events[0]->ID, 'jeero/import/post/theater', true ) );
	}

	/**
	 * Tests if two performances of one production keep separate Jeero source metadata.
	 *
	 * @since	1.?
	 */
	function test_jeero_source_data_belongs_to_each_performance_of_a_production() {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			$this->markTestSkipped( 'The Abilities API requires WordPress 6.9 or newer.' );
		}
		$shared_production = function( $response ) {
			$body = json_decode( $response['body'], true );
			$body[0]['data']['production']['ref'] = 'shared-production';
			$response['body'] = wp_json_encode( $body );
			return $response;
		};
		add_filter( 'jeero/mother/post/response/endpoint=inbox/big', $shared_production, 11 );
		$this->import_event();
		$calendar = \Jeero\Calendars\get_calendar( $this->calendar );
		$first = $calendar->get_events();
		$this->assertCount( 1, $first );
		$production_id = $calendar->get_event_by_ref( 'shared-production', 'veezi' );
		$this->assertNotEmpty( $production_id );
		$this->assertSame( '', get_post_meta( $production_id, '_jeero_event_data', true ) );
		$second_ref = 'second-performance';
		$change = function( $response ) use ( $second_ref ) {
			$body = json_decode( $response['body'], true );
			$body[0]['data']['ref'] = $second_ref;
			$body[0]['data']['start'] = '2026-12-01 20:00:00';
			$response['body'] = wp_json_encode( $body );
			return $response;
		};
		add_filter( 'jeero/mother/post/response/endpoint=inbox/big', $change, 11 );
		try {
			Inbox\pickup_items();
		} finally {
			remove_filter( 'jeero/mother/post/response/endpoint=inbox/big', $change, 11 );
			remove_filter( 'jeero/mother/post/response/endpoint=inbox/big', $shared_production, 11 );
		}
		$events = $calendar->get_events();
		$this->assertCount( 2, $events );
		$this->assertSame( $first[0]['start'], $calendar->get_event( $first[0]['ref'] )['start'] );
		$this->assertSame( '2026-12-01 20:00:00', $calendar->get_event( $second_ref )['start'] );
		$this->assertCount( 1, get_posts( array( 'post_type' => WPT_Production::post_type_name, 'post_status' => 'any' ) ) );
		$performances = get_posts( array( 'post_type' => WPT_Event::post_type_name, 'post_status' => 'any' ) );
		$this->assertCount( 2, $performances );
		foreach ( $performances as $performance ) {
			$this->assertEquals( $production_id, get_post_meta( $performance->ID, WPT_Production::post_type_name, true ) );
			$this->assertNotEmpty( get_post_meta( $performance->ID, '_jeero_event_data', true ) );
		}
		$this->assertSame( '', get_post_meta( $production_id, '_jeero_event_data', true ) );
	}

	/**
	 * Tests if Theater preserves standardized Jeero fields and adds current production page details.
	 *
	 * @since	1.?
	 */
	function test_calendar_preserves_standardized_data_and_adds_production_page_details() {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			$this->markTestSkipped( 'The Abilities API requires WordPress 6.9 or newer.' );
		}
		$this->import_event();
		$calendar = Jeero\Calendars\get_calendar( $this->calendar );
		$production = $this->get_events( array( 'post_status' => 'any' ) )[0];
		$performance = get_posts( array( 'post_type' => WPT_Event::post_type_name, 'post_status' => 'any' ) )[0];
		wp_update_post( array( 'ID' => $production->ID, 'post_title' => 'Edited title', 'post_content' => '<p>Edited description.</p>', 'post_status' => 'publish' ) );
		wp_set_object_terms( $production->ID, array( 'Edited category' ), 'category' );
		foreach ( array(
			'event_date' => '2030-02-03 19:30:00', 'enddate' => '2030-02-03 21:00:00',
			'venue' => 'Edited venue', 'city' => 'Edited city',
			'tickets_url' => 'https://example.org/edited', 'tickets_status' => WPT_Event::tickets_status_soldout,
		) as $key => $value ) {
			update_post_meta( $performance->ID, $key, $value );
		}
		delete_post_meta( $performance->ID, '_wpt_event_tickets_price' );
		add_post_meta( $performance->ID, '_wpt_event_tickets_price', '25|Ticket (voorverkoop)' );
		$source = get_post_meta( $performance->ID, '_jeero_event_data', true );
		$source['tickets'] = array( 'total' => 100, 'available' => 0 );
		update_post_meta( $performance->ID, '_jeero_event_data', wp_slash( $source ) );

		$native_queries = 0;
		$observe = function( $args ) use ( &$native_queries ) {
			++$native_queries;
			return $args;
		};
		add_filter( 'wpt/events/get/args', $observe );
		try {
			$events = $calendar->get_events( array( 'date' => substr( $source['start'], 0, 10 ), 'query' => 'A test event', 'category' => 'Category A', 'status' => 'onsale', 'subscription_id' => 'a fake ID', 'theater' => 'veezi' ) );
		} finally {
			remove_filter( 'wpt/events/get/args', $observe );
		}
		$this->assertSame( 1, $native_queries );
		$this->assertCount( 1, $events );
		$event = $events[0];
		foreach ( $source as $key => $value ) {
			$this->assertSame( $value, $event[ $key ] );
		}
		$this->assertSame( $production->ID, $event['post_id'] );
		$this->assertSame( get_permalink( $production->ID ), $event['permalink'] );
		$this->assertSame( 'publish', $event['post_status'] );
		$this->assertSame( $event, $calendar->get_event( '123' ) );
		$this->assertSame( 2, $calendar->list_event_categories()['count'] );
		$this->assertSame( 'A test event', Jeero\Abilities\Data\get_events( array() )['events'][0]['production']['title'] );
		$this->assertCount( 0, $calendar->get_events( array( 'query' => 'Edited title' ) ) );
		$this->assertCount( 0, $calendar->get_events( array( 'category' => 'Edited category' ) ) );
		$this->assertCount( 0, $calendar->get_events( array( 'date' => '2030-02-03' ) ) );
		$this->assertCount( 0, $calendar->get_events( array( 'status' => 'soldout' ) ) );
		$this->assertCount( 0, $calendar->get_events( array( 'subscription_id' => 'other' ) ) );
		$this->assertWPError( $calendar->get_events( array( 'date' => '2030-02-30' ) ) );
	}

	/**
	 * Tests if Theater omits missing source data and unrelated or orphaned dates.
	 *
	 * @since	1.?
	 */
	function test_calendar_omits_missing_source_metadata_and_unrelated_or_orphaned_dates() {
		$this->import_event();
		$calendar = Jeero\Calendars\get_calendar( $this->calendar );
		$production = $this->get_events( array( 'post_status' => 'any' ) )[0];
		$performance = get_posts( array( 'post_type' => WPT_Event::post_type_name, 'post_status' => 'any' ) )[0];
		$source = get_post_meta( $performance->ID, '_jeero_event_data', true );
		foreach ( array( '_jeero_event_data', '_jeero_event_calendar', 'jeero/import/post/subscription', 'jeero/import/post/theater' ) as $key ) {
			delete_post_meta( $performance->ID, $key );
		}
		$unrelated_production = wp_insert_post( array( 'post_type' => WPT_Production::post_type_name, 'post_title' => 'Unrelated production', 'post_status' => 'publish' ) );
		foreach ( array( $unrelated_production, 0 ) as $production_id ) {
			$other = wp_insert_post( array( 'post_type' => WPT_Event::post_type_name, 'post_status' => 'publish' ) );
			update_post_meta( $other, WPT_Production::post_type_name, $production_id );
			update_post_meta( $other, '_wpt_source_ref', 'unrelated' );
			update_post_meta( $other, 'event_date', '2030-02-03 19:30:00' );
		}
		$this->assertCount( 0, $calendar->get_events() );
		update_post_meta( $performance->ID, '_jeero_event_data', wp_slash( $source ) );
		$events = $calendar->get_events();
		$this->assertCount( 1, $events );
		$this->assertSame( '123', $events[0]['ref'] );
		$this->assertSame( 'a fake ID', $events[0]['subscription_id'] );
		$this->assertSame( 'veezi', $events[0]['theater'] );
		$this->assertSame( 'A test event', $events[0]['production']['title'] );
		$this->assertSame( $source['production']['description'], $events[0]['production']['description'] );
		$this->assertSame( array( 'Category A', 'Category B' ), $events[0]['production']['categories'] );
		wp_trash_post( $production->ID );
		$this->assertCount( 0, $calendar->get_events() );
	}

	function test_jeero_tickets_button_event_template_field_renders_for_event() {

		global $wp_theatre;

		$this->import_event();

		$args   = array(
			'status' => array( 'draft' ),
		);
		$events = $wp_theatre->events->get( $args );

		$actual = apply_filters(
			'wpt/event/template/field/value',
			'',
			'jeero_tickets_button',
			array(),
			array(),
			$events[0]
		);

		$this->assertStringContainsString( 'href="https://slimndap.com"', $actual );
		$this->assertStringContainsString( 'data-jeero-ticket-status="onsale"', $actual );

	}

	function test_event_date_remark_uses_custom_template() {

		global $wp_theatre;

		add_filter( 'jeero/mother/post/response/endpoint=inbox/big', function( $response ) {
			$body = json_decode( $response[ 'body' ] );
			$body[ 0 ]->data->custom->PerfFlagsDescription = '70mm';
			$response[ 'body' ] = json_encode( $body );

			return $response;
		}, 11 );

		$settings = array(
			$this->calendar.'/import/post_fields' => array(
				'remark' => array(
					'template' => '{{ PerfFlagsDescription }}',
				),
			),
		);

		$this->import_event( $settings );

		$args = array(
			'status' => array( 'draft' ),
		);
		$events = $wp_theatre->events->get( $args );

		$this->assertEquals( '70mm', $events[ 0 ]->remark() );

	}

	function test_event_date_remark_is_cleared_when_source_becomes_empty() {

		global $wp_theatre;

		add_filter( 'jeero/mother/post/response/endpoint=inbox/big', function( $response ) {
			$body = json_decode( $response[ 'body' ] );
			$body[ 0 ]->data->custom->PerfFlagsDescription = '70mm';
			$response[ 'body' ] = json_encode( $body );

			return $response;
		}, 11 );

		$settings = array(
			$this->calendar.'/import/post_fields' => array(
				'remark' => array(
					'template' => '{{ PerfFlagsDescription }}',
					'update' => 'always',
				),
			),
		);

		$this->import_event( $settings );

		add_filter( 'jeero/mother/post/response/endpoint=inbox/big', function( $response ) {
			$body = json_decode( $response[ 'body' ] );
			$body[ 0 ]->data->custom->PerfFlagsDescription = '';
			$response[ 'body' ] = json_encode( $body );

			return $response;
		}, 12 );

		Inbox\pickup_items();

		$args = array(
			'status' => array( 'draft' ),
		);
		$events = $wp_theatre->events->get( $args );

		$this->assertSame( '', get_post_meta( $events[ 0 ]->ID, 'remark', true ) );

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
