<?php
namespace Jeero\Calendars;


/**
 * Global variable that tracks all active calendars in Jeero.
 * 
 * @since	1.15
 * @var		array
 */
$_jeero_calendars = array();

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
 * @since	1.12	Added Sugar_Calendar.
 * @since	1.15	Now @uses Jeero\Calendars::get_calendars() to get all available calendars.
 *
 * @return	array	All active calendars.
 */
function get_active_calendars() {
	
	$active_calendars = array();

	$calendars = get_calendars();
	
	foreach( $calendars as $calendar ) {
		if ( $calendar->is_active() ) {
			$active_calendars[] = $calendar;
		}
	}

	return $active_calendars;
	
}

/**
 * Gets all available calendars.
 * 
 * @since	1.15
 * @return	array()
 */
function get_calendars() {
	global $_jeero_calendars;		
	return $_jeero_calendars;		
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

/**
 * Registers a new Calendar.
 * 
 * @since	1.15
 * @param 	string	$classname	The full class name of the Calendar (including namespace).
 * @return 	void
 */
function register_calendar( $classname ) {

	global $_jeero_calendars;	
	$_jeero_calendars[] = new $classname();

}

	