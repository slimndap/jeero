<?php
namespace Jeero\Calendars;

add_action( 'init', __NAMESPACE__.'\add_import_filters' );

/**
 * Attaches all calendar imports to the corresponding inbox filter.
 * 
 * @since	1.?
 * @since	1.4	Added support for the subscription param.
 * @return 	void
 */
function add_import_filters() {
	
	$calendars = get_active_calendars();
	foreach( $calendars as $calendar ) {		
		add_filter( 'jeero/inbox/process/item/import/calendar='.$calendar->get( 'slug' ), array( $calendar, 'import' ), 10, 5 );		
	}
	
}

/**
 * Get a list of all active calendars.
 *
 * @since	1.0
 * @since	1.2		Added Very_Simple_Event_List.
 * @since	1.1		Added Events_Schedule_Wp_Plugin.
 * @since	1.03	Added Modern_Events_Calendar.
 *
 * @return	array	All active calendars.
 */
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
	
	if ( is_plugin_active( 'very-simple-event-list/vsel.php' ) ) {
		$slugs[] = 'Very_Simple_Event_List';
	}
		
	if ( is_plugin_active( 'gdlr-event/gdlr-event.php' ) ) {
		$slugs[] = 'GDLR_Events';
	}
		
	$calendars = array();
	
	foreach ( $slugs as $slug ) {
		
		$calendars[] = get_calendar( $slug );
		
	}
	
	return $calendars;
	
}

/**
 * Get as calendar.
 *
 * @since	1.?
 * @param	$slug	string	The slug of the calendar.
 * @return	Calendar
 */
function get_calendar( $slug = '' ) {
	
	$class = __NAMESPACE__.'\\'.$slug;
	if ( class_exists( $class ) ) {
		
		// Calendar found.
		return new $class();
	}

	// Calendar not found, return default Calendar object.
	return new Calendar();	
	
}

/**
 * Gets the calendars field.
 * 
 * @since	1.4
 * @return	array
 */
function get_calendars_field() {

	$choices = array();
	
	foreach( get_active_calendars() as $calendar ) {
		$choices[ $calendar->get( 'slug' ) ] = $calendar->get( 'name' );
	}
	
	if ( empty( $choices ) ) {
		$field = array(
			'name' => 'calendar',
			'label' => 'Calendar plugin',
			'type' => 'error',
			'value' => __( 'Please activate one or more supported calendar plugins.', 'jeero' ),
		);
	} else {
		$field = array(
			'name' => 'calendar',
			'label' => 'Calendar plugin',
			'type' => 'checkbox',
			'choices' => $choices,
			'required' => true,
		);
	}
	
	return $field;
	
}


/**
 * Determines whether a plugin is active.
 *
 * A copy of the WordPress native 'is_plugin_active()' function, which can only be used when inside the admin.
 *
 * @since	1.2
 * @todo	Add support for 'is_plugin_active_for_network()'
 *
 * $param	$plugin_path	string	Path to the plugin file relative to the plugins directory.
 */
function is_plugin_active( $plugin_path ) {
	return in_array( $plugin_path, (array) get_option( 'active_plugins', array() ), true );
}

	