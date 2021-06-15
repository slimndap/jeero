<?php
use Jeero\Subscriptions;
use Jeero\Subscriptions\Subscription;
use Jeero\Admin;
use Jeero\Inbox;

class All_In_One_Event_Calendar_Test extends Post_Based_Calendar_Test {
	
	function __construct() {
		
		parent::__construct();
		
		$this->calendar = 'All_In_One_Event_Calendar';
		
	}

	function test_details_are_imported() {

		global $ai1ec_front_controller;
		
		$this->import_event();
		
		$args = array(
			'post_status' => 'any',
		);		
		$events = $this->get_events( $args );

		$args = array(
			'ID' => $events[ 0 ]->ID,
		);

		$Ai1ec_Event = $ai1ec_front_controller->return_registry( true )->get( 'model.event', $events[ 0 ]->ID );
		
		$actual = date( 'Ymd', $Ai1ec_Event->get( 'start' )->format() );
		$expected = date( 'Ymd', time() + 2 * DAY_IN_SECONDS );
		$this->assertEquals( $expected, $actual );

		$actual = $Ai1ec_Event->get( 'ticket_url' );
		$expected = 'https://slimndap.com';
		$this->assertEquals( $expected, $actual );

		$actual = $Ai1ec_Event->get( 'venue' );
		$expected = 'Paard';
		$this->assertEquals( $expected, $actual );
		
	}
	
	function test_details_are_updated() {
		
		global $ai1ec_front_controller;
		
		$this->import_event();
		
		$args = array(
			'post_status' => 'any',
		);		
		$events = $this->get_events( $args );

		$args = array(
			'ID' => $events[ 0 ]->ID,
		);

		// Manually update venue to 'Paradiso'.
		$Ai1ec_Event = $ai1ec_front_controller->return_registry( true )->get( 'model.event', $events[ 0 ]->ID );
		$Ai1ec_Event->set( 'venue', 'Paradiso' );
		$Ai1ec_Event->save( true );
		
		$actual = $Ai1ec_Event->get( 'venue' );
		$expected = 'Paradiso';
		$this->assertEquals( $expected, $actual );

		// Trigger next import.
		\Jeero\Inbox\pickup_items();
				
		$Ai1ec_Event = $ai1ec_front_controller->return_registry( true )->get( 'model.event', $events[ 0 ]->ID );

		// Venue should be updated to value from import.
		$actual = $Ai1ec_Event->get( 'venue' );
		$expected = 'Paard';
		$this->assertEquals( $expected, $actual );
		
		
	}
	
}