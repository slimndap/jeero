<?php
namespace Jeero\Admin\Notices;

const JEERO_ADMIN_NOTICES_OPTION_KEY = 'jeero/admin/notices';

add_action( 'admin_notices', __NAMESPACE__.'\do_admin_notices' );

function add_error( \WP_Error $error ) {

	add_notification( $error->get_error_message(), 'error' );
	
}

function add_notification( $message, $type = 'info' ) {
	
	$notifications = get_option( JEERO_ADMIN_NOTICES_OPTION_KEY );
	
	$notification = array(
		'message' => $message,
		'type' => $type,
	);
	$notifications[] = $notification;
	
	update_option( JEERO_ADMIN_NOTICES_OPTION_KEY, $notifications );
	
}

function add_success( $message ) {

	add_notification( $message, 'success' );
	
}

function do_admin_notices() {

	$notifications = get_option( JEERO_ADMIN_NOTICES_OPTION_KEY, array() );
	
	foreach( $notifications as $notification ) {
		?><div class="notice notice-<?php echo $notification[ 'type' ]; ?>">
		    <p><?php echo $notification[ 'message' ]; ?></p>
		</div><?php
	}
	
	delete_option( JEERO_ADMIN_NOTICES_OPTION_KEY );
	
}
