<?php
namespace Jeero\Admin\Subscriptions;

use Jeero\Admin;
use Jeero\Mother;

function do_admin_page() {

	if ( isset( $_GET[ 'edit' ] ) ) {
		
	}
	
	echo get_list_table_html();	
	
}

function get_list_table_html() {
	
	ob_start();
	
	$list_table = new List_Table();
	
	?><div class="wrap">
		<h1 class="wp-heading-inline"><?php _e( 'Jeero Ticketsync', 'jeero' ); ?></h1>
		<a href="<?php echo get_new_subscription_url();?>" class="page-title-action"><?php _e( 'Add Subscription', 'jeero' ); ?></a>
		<hr class="wp-header-end"><?php
			
		$list_table->display(); 
		
	?></div><?php
		
	return ob_get_clean();

}

function get_admin_page_url() {
	return admin_url( 'admin.php?page=jeero/subscriptions');
}

function get_new_subscription_url() {	
	return wp_nonce_url( get_admin_page_url(), 'add', 'jeero/nonce' );
}

function add_new_subscription() {
	
	if ( !isset( $_GET[ 'jeero/nonce' ] ) ) {
		return;
	}
	
	if ( !wp_verify_nonce($_GET['jeero/nonce'], 'add') ) {
		return;
	}
	
	$subscription = Mother\subscribe_me();
	
	if ( is_wp_error( $subscription ) ) {
		Admin\add_error( $subscription );
		wp_safe_redirect( get_admin_page_url() );
		exit;
	}
	
	$edit_url = add_query_arg( 'edit', $subscription[ 'ID' ], get_admin_page_url() );

	wp_safe_redirect( $edit_url );
	exit;
	
}
add_action( 'admin_init', __NAMESPACE__.'\add_new_subscription' );

