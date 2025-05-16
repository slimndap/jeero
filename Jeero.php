<?php
/**
 * Plugin Name:     Jeero
 * Plugin URI:      https://jeero.ooo
 * Description:     Synchronizes events and tickets from your existing ticketing solution with popular calendar plugins.
 * Author:          Slim & Dapper
 * Author URI:      https://slimndap.com
 * Version:         1.31.1
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

define( 'Jeero\VERSION', '1.31.1' );
define( 'Jeero\PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'Jeero\PLUGIN_URI', plugin_dir_url( __FILE__ ) );

// Bootstrap plugin on plugins_loaded: include core functionality.
add_action( 'plugins_loaded', function() {
    include_once \Jeero\PLUGIN_PATH . 'includes/Jeero.php';
} );