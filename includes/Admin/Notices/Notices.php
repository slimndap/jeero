<?php
/**	
 * Manages Notifications in the Admin.
 *
 * Notifications are stored in the DB until they are being displayed through the 'admin_notices' hook.
 *
 * @since	1.0
 */
namespace Jeero\Admin\Notices;

const JEERO_ADMIN_NOTICES_OPTION_KEY = 'jeero/admin/notices';

add_action( 'admin_notices', __NAMESPACE__.'\do_admin_notices', 99 );

/**
 * Adds an error notification to the DB.
 * 
 * @since	1.0
 * @param 	WP_Error	$error
 * @return 	void
 */
function add_error( \WP_Error $error ) {

	add_notification( $error->get_error_message(), 'error' );
	
}

/**
 * Adds a notification to the DB.
 * 
 * @since	1.0
 * 			1.25	Fixed a PHP warning.
 * @param 	string	$message
 * @param 	string 	$type (default: 'info')
 * @return 	void
 */
function add_notification( $message, $type = 'info' ) {
	
	$notifications = get_option( JEERO_ADMIN_NOTICES_OPTION_KEY, array() );
	
	$notification = array(
		'message' => $message,
		'type' => $type,
	);
	$notifications[] = $notification;
	
	update_option( JEERO_ADMIN_NOTICES_OPTION_KEY, $notifications );
	
}

/**
 * Adds an success notification to the DB.
 * 
 * @since	1.0
 * @param 	WP_Error	$error
 * @return 	void
 */
function add_success( $message ) {

	add_notification( $message, 'success' );
	
}

/**
 * Outputs all notifications and removes them from the DB.
 * 
 * @since	1.0
 * @return 	void
 */
function do_admin_notices() {

	$notifications = get_option( JEERO_ADMIN_NOTICES_OPTION_KEY, array() );
	
	foreach( $notifications as $notification ) {
		?><div class="notice notice-<?php echo $notification[ 'type' ]; ?>">
		    <p><?php echo $notification[ 'message' ]; ?></p>
		</div><?php
	}
	
	delete_option( JEERO_ADMIN_NOTICES_OPTION_KEY );
	
}
