<?php
/**
 * Plugin Name:     Jeero
 * Plugin URI:      https://jeero.ooo
 * Description:     Synchronizes events and tickets from your existing ticketing solution with popular calendar plugins.
 * Author:          Jeroen Schmit
 * Author URI:      https://jero.ooo
 * Text Domain:     jeero
 * Domain Path:     /languages
 * Version:         0.1.0
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