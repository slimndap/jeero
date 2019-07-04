<?php
/**
 * Handles all Jeero admin screens.
 */
namespace Jeero\Admin;

function add_error( \WP_Error $error ) {
	
	$errors = get_option( 'jeero/admin/errors', array() );
	$errors[] = $error->get_error_message();
	update_option( 'jeero/admin/errors', $errors );
	
}

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
add_action( 'admin_menu', __NAMESPACE__.'\add_menu_item' );

function do_admin_notices() {

	$errors = get_option( 'jeero/admin/errors', array() );
	foreach( $errors as $error ) {
		?><div class="error notice">
		    <p><?php echo $error; ?></p>
		</div><?php
	}
	
	delete_option( 'jeero/admin/errors' );
	
}
add_action( 'admin_notices', __NAMESPACE__.'\do_admin_notices' );
