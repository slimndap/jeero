<?php
/**	
 * Manages the Subscription section in the Admin.
 *
 * Subscriptions are managed by Mother, but Jeero keeps a local copy with Subscription settings.
 * The Subscription settings are always passed as part of any conversation with Mother. 
 * Mother never stores the settings.
 *
 * ### Adding a Subscription
 * 1. User clicks the _Add Subscription_ button.
 * 2. Jeero asks Mother to add a Subscription.
 * 3. Mother creates and returns the ID of the new Subscription.
 * 4. Jeero opens de Edit Subscription Admin screen.
 * 5. Jeero shows a form with the fields that are defined in the Subscription.
 * 6. User clicks the _Save Subscription_ button.
 * 7. Jeero saves the Subscription settings to the DB.
 * 8. Jeero submits the Subscription settings to Mother.
 * 9. Mother validates the settings and returns the Subscription with updated fields and status.
 * 10. The user is redirected back to the form until the Subscription validates (step 5).
 * 11. The user is redirected to the Subscriptions Admin page.
 *
 * ### Viewing all Subscriptions
 * 1. Jeero asks Mother for a list of all Subscriptions.
 * 2. Mother returns a list of all Subscriptions.
 * 3. Jeero adds/updates the Subscriptions in the DB.
 * 4. Jeero shows a list a of Subscriptions.
 *
 */
namespace Jeero\Admin\Subscriptions;

use Jeero\Admin;
use Jeero\Subscriptions;
use Jeero\Inbox;

add_action( 'admin_init', __NAMESPACE__.'\add_new_subscription' );
add_action( 'admin_init', __NAMESPACE__.'\update_subscription' );


/**
 * Outputs the Subscription Admin pages.
 * 
 * @since	1.0
 * @return 	void
 */
function do_admin_page() {

	echo get_admin_page_html();
	
}

/**
 * Gets the HTML for the Edit Subscription Admin page.
 *
 * Builds a form based on the fields of the Subscription.
 * 
 * @param	int		$subscription_id	The ID of the Subscription.
 * @return 	string|WP_Error				The HTML for the Edit Subscription Admin page.
 *										Or an error if there was a problem.
 */
function get_edit_html( $subscription_id ) {
	
	$subscription = Subscriptions\get_subscription( $subscription_id );
	if ( is_wp_error( $subscription ) ) {
		Admin\Notices\add_error( $subscription );
		wp_redirect( get_admin_page_url() );
		exit;
	}
	
	ob_start();
	
	?><div class="wrap">
		<h1><?php _e( 'Edit Subscription', 'jeero' ); ?></h1>
		<form><?php
			wp_nonce_field( 'save', 'jeero/nonce', true, true );
			?><input type="hidden" name="subscription_id" value="<?php echo $subscription_id; ?>">
			<table class="form-table">
				<tbody><?php
					
					foreach( $subscription->get_fields() as $field ) {
						?><tr>
							<th scope="row">
								<label for="blogname"><?php echo $field->get_label(); ?></label>
							</th>
							<td><?php echo $field->get_control_html(); ?></td>
						</tr><?php
					}

				?></tbody>
			</table>
			<p class="submit">
				<input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes">
				<a href="<?php echo get_admin_page_url(); ?>" class="button"><?php _e( 'Cancel', 'Jeero' ); ?></a>
			</p>
		</form>
			
		
	</div><?php
		
	return ob_get_clean();
}

function get_list_table_html() {
	
	ob_start();
	
	$list_table = new List_Table();	
	$list_table->prepare_items();
	
	?><div class="wrap">
		<h1 class="wp-heading-inline"><?php _e( 'Jeero Ticketsync', 'jeero' ); ?></h1>
		<a href="<?php echo get_new_subscription_url();?>" class="page-title-action"><?php _e( 'Add Subscription', 'jeero' ); ?></a>
		<hr class="wp-header-end"><?php
			
		$list_table->display(); 
		
		?><p><?php printf( __( 'Next scheduled sync: %s', 'jeero' ), date_i18n( 'Y-m-d H:i:s', Inbox\get_next_pickup() ) ); ?></p>
	</div><?php
		
	return ob_get_clean();

}

function get_admin_edit_url( $subscription_id ) {
	return add_query_arg( 'edit', $subscription_id, get_admin_page_url() );
}

function get_admin_page_html() {

	if ( isset( $_GET[ 'edit' ] ) ) {
		return get_edit_html( $_GET[ 'edit' ] );
	}
	
	return get_list_table_html( );	
	
}

function get_admin_page_url() {
	return admin_url( 'admin.php?page=jeero/subscriptions');
}

function get_new_subscription_url() {	
	return wp_nonce_url( get_admin_page_url(), 'add', 'jeero/nonce' );
}

/**
 * Handles clicks on the _Add Subscription_ button.
 * 
 * Asks Mother to add a new Subscription.
 * Redirects the user to the Edit Subscription Admin page.
 *
 * @since	1.0
 * @return 	void
 */
function add_new_subscription() {
	
	if ( !isset( $_GET[ 'jeero/nonce' ] ) ) {
		return;
	}
	
	if ( !wp_verify_nonce( $_GET['jeero/nonce'], 'add' ) ) {
		return;
	}
	
	$subscription_id = Subscriptions\add_subscription();

	if ( is_wp_error( $subscription_id ) ) {
		Admin\Notices\add_error( $subscription_id );
		wp_safe_redirect( get_admin_page_url() );
		exit;
	}

	wp_safe_redirect( get_admin_edit_url( $subscription_id ) );
	exit;
	
}

function update_subscription() {

	if ( !isset( $_GET[ 'jeero/nonce' ] ) ) {
		return;
	}
	
	if ( !wp_verify_nonce( $_GET['jeero/nonce'], 'save' ) ) {
		return;
	}
	
	if ( empty( $_GET[ 'subscription_id' ] ) ) {
		return;
	}

	// Save settings to Subscription.
	$subscription = Subscriptions\get_subscription( $_GET[ 'subscription_id' ] );	

	if ( is_wp_error( $subscription ) ) {
		Admin\Notices\add_error( $subscription );
		wp_safe_redirect( get_admin_edit_url( $subscription->get( 'ID' ) ) );			
	}

	$settings = array();	
	foreach( $subscription->get_fields() as $field ) {
		$setting = $field->get_setting_from_form( $_GET );
		$settings[ $field->get( 'name' ) ] = $setting;
	}	
	$subscription->set( 'settings', $settings );
	$subscription->save();
	
	$subscription = Subscriptions\get_subscription( $subscription->get( 'ID' ) );
	$settings = $subscription->get( 'settings' );
	
	if ( \Jeero\Subscriptions\JEERO_SUBSCRIPTIONS_STATUS_SETUP == $subscription->get( 'status' ) ) {	
		Admin\Notices\add_success( sprintf( __( '%s subscription updated. Please enter any missing settings below.', 'jeero' ), $settings[ 'theater' ] ) );	
		wp_safe_redirect( get_admin_edit_url( $subscription->get( 'ID' ) ) );	
	} else {
		Admin\Notices\add_success( sprintf( __( '%s subscription updated.', 'jeero' ), $settings[ 'theater' ] ) );	
		Inbox\pickup_items();
		wp_safe_redirect( get_admin_page_url() );	
	}
	
	exit;
	
}

