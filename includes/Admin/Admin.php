<?php
/**
 * Handles all Jeero admin screens.
 */
namespace Jeero\Admin;

add_action( 'admin_menu', __NAMESPACE__.'\add_menu_item' );
add_action( 'admin_enqueue_scripts', __NAMESPACE__.'\enqueue_scripts' );

/**
 * Adds the Jeero menu to the admin.
 * 
 * @since	1.0
 * @since	1.1		Renamed 'subscriptions' to 'imports'.
 * @since	1.18	Added a debug admin page.
 * @since	1.24.1	Changed parent slug of debug admin page to ' ', to prevent a PHP 8.1+ warning.
 *					@see https://core.trac.wordpress.org/ticket/57579#comment:9
 * @since	1.30	Added Settings and Logs submenus.
 * @return 	void
 */
function add_menu_item() {
	
	add_menu_page(
        'Jeero',
        'Jeero',
        'manage_options',
        'jeero',
        '',
        'dashicons-tickets-alt',
        90
    );

	add_submenu_page(
		'jeero',
        __( 'Jeero Imports', 'jeero' ),
        __( 'Imports', 'jeero' ),
        'manage_options',
        'jeero/imports',
        __NAMESPACE__.'\Subscriptions\do_admin_page',
    );
    
	add_submenu_page(
		'jeero',
        __( 'Jeero Settings', 'jeero' ),
        __( 'Settings' ),
        'manage_options',
        'jeero/settings',
        __NAMESPACE__.'\Settings\do_admin_page',
    );
    
    if ( \get_option( 'jeero/enable_logs' ) ) {
		add_submenu_page(
			'jeero',
	        __( 'Jeero Logs', 'jeero' ),
	        __( 'Logs', 'jeero' ),
	        'manage_options',
	        'jeero/debug',
	        __NAMESPACE__.'\Debug\do_admin_page',
	    );
	}
    
    remove_submenu_page( 'jeero', 'jeero' );
    
}

/**
 * Enqueues Jeero admin scripts and styles.
 * 
 * @since	1.5
 * @since	1.10	Only enqueue on Jeero admin pages.
 * @since	1.17	Enqueue on all pages Jeero now leaves footprints all over the place.
 *					Eg. meta boxes on all event admin pages.
 * @return 	void
 */
function enqueue_scripts( ) {
	
	wp_enqueue_script( 'jeero/admin', \Jeero\PLUGIN_URI . 'assets/js/admin.js', array( 'jquery' ), \Jeero\VERSION );
	wp_enqueue_style( 'jeero/admin', \Jeero\PLUGIN_URI . 'assets/css/admin.css', array(), \Jeero\VERSION, 'all' );

}


/**
 * Redirects admin users.
 *
 * @since	1.10
 * @param	string	$url		The URL for the redirect.
 * @return	void
 */
function redirect( $url ) {

	// Should we do redirects?
	// Filter is used by unit tests to prevents redirects.
	$do_redirects = apply_filters( 'jeero\admin\do_redirects', true );

	// Bail if we are not doing redirects.
	if ( !$do_redirects ) {
		return $url;
	}

	// Redirect and exit.
	\wp_safe_redirect( $url );
	exit;

}

