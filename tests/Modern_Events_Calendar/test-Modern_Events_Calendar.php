<?php
class Modern_Events_Calendar_Test extends Post_Based_Calendar_Test {
	
	function __construct() {
		
		parent::__construct();
		
		$this->calendar = 'Modern_Events_Calendar';
		
	}

	function test_excerpt_is_updated_after_second_import() {

		// Skip this test. Modern Events Calendar does not support excerpts.
		$this->assertTrue( true );

	}
	
	function test_has_venue() {

		$settings = array(
		);

		$this->import_event( $settings );

		$args = array(
			'post_status' => 'draft',
		);
		$events = $this->get_events( $args );
		
		$actual = get_term( \MEC::getInstance( 'app.libraries.main' )->get_master_location_id( $events[ 0 ]->ID ) )->name;
		$expected = 'Paard';
		$this->assertEquals( $expected, $actual );
		
	}	

	function test_imported_event_is_in_location_taxonomy() {

		$this->import_event();

		$args = array(
			'post_status' => 'any',
		);
		$events = $this->get_events( $args );

		$event = $events[0];

		$location = get_term_by( 'name', 'Paard', 'mec_location' );

		$this->assertNotEmpty( $location, 'The Paard location must exist before checking assigned terms.' );

		$assigned_location_ids = wp_list_pluck( wp_get_object_terms( $event->ID, 'mec_location' ), 'term_id' );

		$this->assertContains( $location->term_id, $assigned_location_ids, 'Jeero should assign the MEC location taxonomy during import so location filters find the event.' );

	}
		
}
