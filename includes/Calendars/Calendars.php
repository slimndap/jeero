<?php
namespace Jeero\Calendars;

add_action( 'init', __NAMESPACE__.'\add_import_filters' );

function add_import_filters() {
	
	$calendars = get_active_calendars();
	foreach( $calendars as $calendar ) {		

		add_filter( 'jeero/inbox/process/item/import/calendar='.$calendar->get( 'slug' ), array( $calendar, 'import' ), 10, 4 );		

		add_action( 'wp_footer', array( $calendar, 'do_footprint' ) );

	}
	
}

function get_active_calendars() {
	
	$slugs = array();
	
	if ( class_exists( 'WP_Theatre' ) ) {
		$slugs[] = 'Theater_For_WordPress';
	}
	
	if ( class_exists( 'Tribe__Events__Main' ) ) {
		$slugs[] = 'The_Events_Calendar';
	}
	
	if ( class_exists( 'Ai1ec_Front_Controller' ) ) {
		$slugs[] = 'All_In_One_Event_Calendar';
	}
		
	if ( defined( 'MECEXEC' ) ) {
		$slugs[] = 'Modern_Events_Calendar';
	}
	
	if ( defined( 'WCS_FILE' ) ) {
		$slugs[] = 'Events_Schedule_Wp_Plugin';
	}
		
	$calendars = array();
	
	foreach ( $slugs as $slug ) {
		
		$calendars[] = get_calendar( $slug );
		
	}
	
	return $calendars;
	
}

function get_calendar( $slug = '' ) {
	
	$class = __NAMESPACE__.'\\'.$slug;
	if ( class_exists( $class ) ) {
		return new $class();
	}

	return new Calendar();	
	
}

	