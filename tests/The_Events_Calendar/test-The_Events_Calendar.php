<?php
class The_Events_Calendar_Test extends Post_Based_Calendar_Test {
	
	function __construct() {
		
		parent::__construct();
		
		$this->calendar = 'The_Events_Calendar';
		
	}
	
	function get_events( $args = array() ) {

		$defaults = array(
			'post_type' => array( 'tribe_events', 'fake' ),
		);
		
		$args = wp_parse_args( $args, $defaults );
		
		return \get_posts( $args );
		
	}
	
	function test_event_has_startdate() {
		
		$this->import_event();

		$args = array(
			'post_status' => array( 'any' ),
		);		
		$events = $this->get_events( $args );	

		$expected = date( 'Ymd', time() + 48 * HOUR_IN_SECONDS );
		$actual = tribe_get_start_date( $events[0]->ID, true, 'Ymd' );
		
		$this->assertEquals( $expected, $actual );
			
		
	}

	/**
	 * Tests if The Events Calendar imports the correct start and end times.
	 *
	 * Until 0.15.2 Jeero was incorrectly localizing start and end times.
	 *
	 * @since	0.15.3
	 */
	function test_has_correct_times() {
		
		// Set website time zone to non-UTC.
		update_option( 'gmt_offset', +2 );
		
		$this->import_event();

		$args = array(
			'post_status' => 'any',
			'meta_query' => array(
				array(
					'key' => 'jeero/the_events_calendar/veezi/ref',
					'value' => 123,					
				),
			),
		);
		
		$events = $this->get_events( $args );	

		$actual = tribe_get_start_time( $events[ 0 ]->ID, 'H:i' );
		$expected = date( 'H:i', current_time( 'timestamp' ) + 48 * HOUR_IN_SECONDS );
		$this->assertEquals( $expected, $actual );
		
		$actual = tribe_get_end_time( $events[ 0 ]->ID, 'H:i' );
		$expected = date( 'H:i', current_time( 'timestamp' ) + 90 * MINUTE_IN_SECONDS + 48 * HOUR_IN_SECONDS );
		$this->assertEquals( $expected, $actual );
		
	}

	function test_has_venue() {

		$settings = array(
			'theater' => 'veezi',
			'calendar' => array( $this->calendar ),
			$this->calendar.'/import/status' => 'publish',
		);

		$this->import_event( $settings );

		$args = array(
			'post_status' => 'any',
		);
		$events = $this->get_events( $args );

		$actual = tribe_get_venue( $events[ 0 ]->ID );
		$expected = 'Paard';
		$this->assertEquals( $expected, $actual );
		
	}	

	function test_venue_uses_default_templates() {

		$this->import_event( );

		$args = array(
			'post_status' => 'draft',
		);
		$events = $this->get_events( $args );

		$actual = tribe_get_venue( $events[ 0 ]->ID );
		$expected = 'Paard';
		$this->assertEquals( $expected, $actual );

		$actual = tribe_get_venue_object( tribe_get_venue_id( $events[ 0 ]->ID ) )->city;
		$expected = 'Den Haag';
		$this->assertEquals( $expected, $actual );
		
	}	


	function test_venue_uses_custom_templates() {

		$settings = array(
			$this->calendar.'/import/post_fields' => array(
				'venue_Title' => array(
					'template' => '{{venue.title}} with custom template',
				),
				'venue_Address' => array(
					'template' => 'The address for {{venue.title}}',
				),
			),
		);

		$this->import_event( $settings );

		$args = array(
			'post_status' => 'draft',
		);
		$events = $this->get_events( $args );

		$actual = tribe_get_venue( $events[ 0 ]->ID );
		$expected = 'Paard with custom template';
		$this->assertEquals( $expected, $actual );

		$actual = tribe_get_venue_object( tribe_get_venue_id( $events[ 0 ]->ID ) )->address;
		$expected = 'The address for Paard';
		$this->assertEquals( $expected, $actual );
		
	}	
	
	function test_inbox_serie_is_imported() {
		
		add_filter( 'jeero/mother/post/response/endpoint=inbox/big', function( $response, $endpoint, $args ) {	
			
			$body = json_decode( $response[ 'body' ], true );
			$body[ 0 ][ 'data' ][ 'production' ][ 'ref' ] = 456;
			$response[ 'body' ] = json_encode( $body );
			return $response;
			
		}, 11, 3 );

		$settings = array(
			$this->calendar.'/import/use_series' => array( 1 ),
		);

		$this->import_event( $settings );

		$args = array(
			'post_type' => \TEC\Events_Pro\Custom_Tables\V1\Series\Post_Type::POSTTYPE,	
			'post_status' => 'draft',
		);
		$series = get_posts( $args );
		
		$actual = count( $series );
		$expected = 1;
		$this->assertEquals( $expected, $actual );
		
		
		
	}

	/**
	 * Tests if venue title is not overwritten after second import.
	 * 
	 * @since	1.22
	 */
	function test_venue_is_not_updated_after_second_import() {

		// Start first import.
		$this->import_event( );

		// Update venue title of first event.
		$args = array(
			'post_status' => 'draft',
		);
		$events = $this->get_events( $args );

		$actual = tribe_get_venue( $events[ 0 ]->ID );
		$expected = 'Paard';
		$this->assertEquals( $expected, $actual );

		$args = array(
			'ID' => tribe_get_venue_id( $events[ 0 ]->ID ),
			'post_title' => 'Paradiso',
		);
		wp_update_post( $args );

		$args = array(
			'post_status' => array( 'draft' ),
		);		
		$events = $this->get_events( $args );
		
		$actual = tribe_get_venue( $events[ 0 ]->ID );
		$expected = 'Paradiso';
		$this->assertEquals( $expected, $actual );

		// Start second import.
		wp_cache_delete( 'Paard', 'jeero/tribe_venue' );
		Jeero\Inbox\pickup_items();
		
		$args = array(
			'post_status' => array( 'draft' ),
		);
		$events = $this->get_events( $args );
		
		$actual = tribe_get_venue( $events[ 0 ]->ID );
		$expected = 'Paradiso';
		$this->assertEquals( $expected, $actual );
		
	}

	/**
	 * Tests if venue title is overwritten after second import.
	 * 
	 * @since	1.22
	 */
	function test_venue_is_updated_after_second_import() {

		$settings = array(
			'theater' => 'veezi',
			'calendar' => array( $this->calendar ),
			$this->calendar.'/import/post_fields' => array(
				'venue_Title' => array(
					'update' => 'always',
				),
			),
		);	

		// Start first import.
		$this->import_event( $settings );

		// Update venue title of first event.
		$args = array(
			'post_status' => 'draft',
		);
		$events = $this->get_events( $args );

		$actual = tribe_get_venue( $events[ 0 ]->ID );
		$expected = 'Paard';
		$this->assertEquals( $expected, $actual );

		$args = array(
			'ID' => tribe_get_venue_id( $events[ 0 ]->ID ),
			'post_title' => 'Paradiso',
		);
		wp_update_post( $args );

		$args = array(
			'post_status' => array( 'draft' ),
		);		
		$events = $this->get_events( $args );
		
		$actual = tribe_get_venue( $events[ 0 ]->ID );
		$expected = 'Paradiso';
		$this->assertEquals( $expected, $actual );

		// Start second import.
		wp_cache_delete( 'Paard', 'jeero/tribe_venue' );
		Jeero\Inbox\pickup_items();
		
		$args = array(
			'post_status' => array( 'draft' ),
		);
		$events = $this->get_events( $args );
		
		$actual = tribe_get_venue( $events[ 0 ]->ID );
		$expected = 'Paard';
		$this->assertEquals( $expected, $actual );
		
	}

	/**
	 * Tests if venue city is not overwritten after second import.
	 * 
	 * @since	1.22
	 */
	function test_venue_city_is_not_updated_after_second_import() {

		// Start first import.
		$this->import_event( );

		// Update venue city of first event.
		$args = array(
			'post_status' => 'draft',
		);
		$events = $this->get_events( $args );

		$actual = tribe_get_city( $events[ 0 ]->ID );
		$expected = 'Den Haag';
		$this->assertEquals( $expected, $actual );

		tribe_update_venue( tribe_get_venue_id( $events[ 0 ]->ID ), array(
			'City' => 'Delft'
		) );
		
		$args = array(
			'post_status' => array( 'draft' ),
		);		
		$events = $this->get_events( $args );
		
		$actual = tribe_get_city( $events[ 0 ]->ID );
		$expected = 'Delft';
		$this->assertEquals( $expected, $actual );

		// Start second import.
		wp_cache_delete( 'Paard', 'jeero/tribe_venue' );
		Jeero\Inbox\pickup_items();
		
		$args = array(
			'post_status' => array( 'draft' ),
		);
		$events = $this->get_events( $args );
		
		$actual = tribe_get_city( $events[ 0 ]->ID );
		$expected = 'Delft';
		$this->assertEquals( $expected, $actual );
		
	}
	
	/**
	 * Tests if venue city is overwritten after second import.
	 * 
	 * @since	1.22
	 */
	function test_venue_city_is_updated_after_second_import() {

		$settings = array(
			'theater' => 'veezi',
			'calendar' => array( $this->calendar ),
			$this->calendar.'/import/post_fields' => array(
				'venue_City' => array(
					'update' => 'always',
				),
			),
		);	

		// Start first import.
		$this->import_event( $settings );

		// Update venue city of first event.
		$args = array(
			'post_status' => 'draft',
		);
		$events = $this->get_events( $args );

		$actual = tribe_get_city( $events[ 0 ]->ID );
		$expected = 'Den Haag';
		$this->assertEquals( $expected, $actual );

		tribe_update_venue( tribe_get_venue_id( $events[ 0 ]->ID ), array(
			'City' => 'Delft'
		) );
		
		$args = array(
			'post_status' => array( 'draft' ),
		);		
		$events = $this->get_events( $args );
		
		$actual = tribe_get_city( $events[ 0 ]->ID );
		$expected = 'Delft';
		$this->assertEquals( $expected, $actual );

		// Start second import.
		wp_cache_delete( 'Paard', 'jeero/tribe_venue' );
		Jeero\Inbox\pickup_items();
		
		$args = array(
			'post_status' => array( 'draft' ),
		);
		$events = $this->get_events( $args );
		
		$actual = tribe_get_city( $events[ 0 ]->ID );
		$expected = 'Den Haag';
		$this->assertEquals( $expected, $actual );
		
	}

	function test_inbox_empty_status_is_imported_as_scheduled() {
		
		$this->import_event();

		$args = array(
			'post_status' => array( 'any' ),
		);		
		$events = $this->get_events( $args );
		$event = tribe_get_event( $events[ 0 ]->ID );
		
		$expected = '';
		$actual = $event->event_status;
		
		$this->assertEquals( $expected, $actual );	
		
	}
	
	function test_inbox_cancelled_status_is_imported() {
		
		add_filter( 'jeero/mother/post/response/endpoint=inbox/big', function( $response, $endpoint, $args ) {	
			
			$body = json_decode( $response[ 'body' ], true );
			$body[ 0 ][ 'data' ][ 'status' ] = 'cancelled';
			$response[ 'body' ] = json_encode( $body );
			return $response;
			
		}, 11, 3 );

		$this->import_event();

		$args = array(
			'post_status' => array( 'any' ),
		);		
		$events = $this->get_events( $args );
		$event = tribe_get_event( $events[ 0 ]->ID );
		
		$expected = 'canceled';
		$actual = $event->event_status;
		
		$this->assertEquals( $expected, $actual );			
		
	}

}