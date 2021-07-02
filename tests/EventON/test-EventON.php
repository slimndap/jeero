<?php
class EventON_Test extends Post_Based_Calendar_Test {
	
	function __construct() {
		
		parent::__construct();
		
		$this->calendar = 'EventON';
		
	}

	function test_has_subtitle() {

		$settings = array(
			$this->calendar.'/import/post_fields' => array(
				'subtitle' => array(
					'template' => '{{ subtitle }}',
				),
			),
		);

		$this->import_event( $settings );

		$args = array(
			'post_status' => 'draft',
		);
		$events = $this->get_events( $args );

		$actual = get_post_meta( $events[ 0 ]->ID, 'evcal_subtitle', true );
		$expected = 'The subtitle';
		$this->assertEquals( $expected, $actual );
		
	}	
	
}