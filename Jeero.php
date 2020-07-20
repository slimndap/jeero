<?php
/**
 * Plugin Name:     Jeero
 * Plugin URI:      https://slimndap.com
 * Description:     Synchronizes events and tickets from your existing ticketing solution with popular calendar plugins.
 * Author:          Slim & Dapper
 * Author URI:      https://slimndap.com
 * Version:         1.0.2
 *
 * @package         Jeero
 */

/**
 * Bail if called directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
    return;
}

define( 'Jeero\PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

include_once \Jeero\PLUGIN_PATH.'includes/Jeero.php';