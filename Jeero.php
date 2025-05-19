<?php
/**
 * Plugin Name:     Jeero
 * Plugin URI:      https://jeero.ooo
 * Description:     Synchronizes events and tickets from your existing ticketing solution with popular calendar plugins.
 * Author:          Slim & Dapper
 * Author URI:      https://slimndap.com
 * Version:         1.31.4
 * Text Domain: 	jeero
 *
 * @package         Jeero
 */

/**
 * Bail if called directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
    return;
}


/**
 * Bootstrap core plugin functionality on the 'init' hook.
 *
 * Defines plugin constants (version, path, URI), loads the main Jeero loader,
 * registers calendar import filters, and seeds the first Inbox pickup into WP-Cron.
 * Calling add_import_filters() here ensures calendar import callbacks are attached
 * before any inbox items are processed. Calling schedule_next_pickup()
 * on every init is safe (it no-ops if an event is already scheduled) and
 * ensures that WP-Cron has a job to process incoming items shortly after activation.
 */
add_action( 'init', function() {

    define( 'Jeero\VERSION', '1.31.4' );
    define( 'Jeero\PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
    define( 'Jeero\PLUGIN_URI', plugin_dir_url( __FILE__ ) );

    include_once \Jeero\PLUGIN_PATH . 'includes/Jeero.php';
    \Jeero\Calendars\add_import_filters();
    \Jeero\Inbox\schedule_next_pickup();
} );