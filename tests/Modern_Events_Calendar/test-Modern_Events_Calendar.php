<?php
class Modern_Events_Calendar_Test extends Post_Based_Calendar_Test {
	
	function __construct() {
		
		parent::__construct();
		
		$this->calendar = 'Modern_Events_Calendar';
		
	}

	function test_excerpt_is_updated_after_second_import() {
		// Skip this test. Modern Events Calendar does not support excerpts.
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
		
}