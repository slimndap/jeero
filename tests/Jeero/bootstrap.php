<?php
/**
 * PHPUnit bootstrap file
 *
 * @package Jeero
 */

$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	echo "Could not find $_tests_dir/includes/functions.php, have you run bin/install-wp-tests.sh ?" . PHP_EOL; // WPCS: XSS ok.
	exit( 1 );
}

// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
	require dirname( dirname( __FILE__ ) ) . '/../jeero.php';
	$mcp_adapter = getenv( 'JEERO_MCP_ADAPTER_PLUGIN' );
	if ( $mcp_adapter ) {
		require_once $mcp_adapter;
	}
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );
define('WP_ALLOW_REPAIR', true);
// Load PHPUnit Polyfills library.
require 'vendor/yoast/phpunit-polyfills/phpunitpolyfills-autoload.php';

// Start up the WP testing environment.
require $_tests_dir . '/includes/bootstrap.php';

if ( getenv( 'JEERO_MCP_ADAPTER_PLUGIN' ) ) {
	// Keep transport hooks in the baseline restored between integration tests.
	\WP\MCP\Core\McpAdapter::instance()->init();
}

require 'tests/includes/jeero.php';
