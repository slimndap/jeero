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
 * @return 	void
 */
function add_menu_item() {
	
	add_menu_page(
        __( 'Jeero Subscriptions', 'jeero' ),
        'Jeero',
        'manage_options',
        'jeero/subscriptions',
        __NAMESPACE__.'\Subscriptions\do_admin_page',
        'dashicons-tickets-alt',
        90
    );
    
}

