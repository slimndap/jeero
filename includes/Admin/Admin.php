<?php
/**
 * Handles all Jeero admin screens.
 */
namespace Jeero\Admin;

add_action( 'admin_menu', __NAMESPACE__.'\add_menu_item' );

/**
 * Adds the Jeero menu to the admin.
 * 
 * @since	1.0
 * @since	1.1	Renamed 'subscriptions' to 'imports'.
 * @return 	void
 */
function add_menu_item() {
	
	add_menu_page(
        __( 'Jeero Imports', 'jeero' ),
        'Jeero',
        'manage_options',
        'jeero/imports',
        __NAMESPACE__.'\Subscriptions\do_admin_page',
        'dashicons-tickets-alt',
        90
    );
    
}

