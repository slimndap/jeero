<?php
/**	
 * Manages the Subscription section in the Admin.
 *
 * Subscriptions are managed by Mother, but Jeero keeps a local copy with Subscription settings.
 * The Subscription settings are always passed as part of most conversations with Mother. 
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
use Jeero\Calendars;

add_action( 'admin_init', __NAMESPACE__.'\add_new_subscription' );
add_action( 'admin_init', __NAMESPACE__.'\process_activate' );
add_action( 'admin_init', __NAMESPACE__.'\process_deactivate' );
add_action( 'admin_init', __NAMESPACE__.'\process_form' );

add_action( 'admin_notices', __NAMESPACE__.'\show_no_active_calendars_warning' );
add_action( 'admin_notices', __NAMESPACE__.'\show_wp_cron_disabled_error' );

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
 * Processes click on the 'Activate' link of an import.
 * @since 	1.?
 * @since	1.21	Redirects back to the list with inactive imports.
 *
 */
function process_activate() {
	
	if ( ! current_user_can( 'manage_options' ) ) {
		return;	
	}
	
	if ( !isset( $_GET[ 'jeero/nonce' ] ) ) {
		return;
	}
	
	if ( !wp_verify_nonce( $_GET['jeero/nonce'], 'activate' ) ) {
		return;
	}
	
	if ( empty( $_GET[ 'subscription_id' ] ) ) {
		return;
	}

	// Save settings to Subscription.
	$subscription = Subscriptions\get_subscription( sanitize_text_field( $_GET[ 'subscription_id' ] ) );
	if ( \is_wp_error( $subscription ) ) {
		Admin\Notices\add_error( $subscription );
		return Admin\redirect( get_admin_page_url( ) );
	}
	
	$subscription->activate();
	
	$theater = $subscription->get( 'theater' );

	Admin\Notices\add_success( sprintf( __( '%s subscription activated.', 'jeero' ), $theater[ 'title' ] ) );

	return Admin\redirect( add_query_arg( 'inactive', true, get_admin_page_url( ) ) );

}

/**
 * Processes a click on the 'deactivate' link of an import.
 * 
 * @since	1.?
 * @since	1.9		Fixed redirects when trying to deactivate a non-existing import.
 * @since	1.21	No redirects to the list ith active imports.
 *
 * @return void
 */
function process_deactivate() {
	
	if ( ! current_user_can( 'manage_options' ) ) {
		return;	
	}
	
	if ( !isset( $_GET[ 'jeero/nonce' ] ) ) {
		return;
	}
	
	if ( !wp_verify_nonce( $_GET['jeero/nonce'], 'deactivate' ) ) {
		return;
	}
	
	if ( empty( $_GET[ 'subscription_id' ] ) ) {
		return;
	}

	// Save settings to Subscription.
	$subscription = Subscriptions\get_subscription( sanitize_text_field( $_GET[ 'subscription_id' ] ) );
	if ( \is_wp_error( $subscription ) ) {
		Admin\Notices\add_error( $subscription );
		return Admin\redirect( get_admin_page_url( ) );
	}
	
	$subscription->deactivate();
	
	$theater = $subscription->get( 'theater' );

	Admin\Notices\add_success( sprintf( __( '%s subscription deactivated.', 'jeero' ), $theater[ 'title' ] ) );

	return Admin\redirect( get_admin_page_url( ) );
	
}

/**
 * Process the Edit Subscription Admin form.
 *
 * Redirects user to Subscriptions Admin page if settings validate.
 * Redirects user back to Subscription Admin form if settings don't validate.
 * 
 * @since	1.0
 * @since	1.0.4	Reload subscription to refresh data from Mother, based on new settings.
 * @since	1.5		No longer stays on form page after saving settings.
 * @return	void
 */
function process_form() {

	if ( !isset( $_GET[ 'jeero/nonce' ] ) ) {
		return;
	}
	
	if ( !wp_verify_nonce( $_GET[ 'jeero/nonce' ], 'save' ) ) {
		return;
	}
	
	if ( empty( $_GET[ 'subscription_id' ] ) ) {
		return;
	}
	
	// Save settings to Subscription.
	$subscription = Subscriptions\get_subscription( sanitize_text_field( $_GET[ 'subscription_id' ] ) );	

	if ( \is_wp_error( $subscription ) ) {
		Admin\Notices\add_error( $subscription );
		Admin\redirect( get_admin_page_url() );
	}

	$settings = array();	
	foreach( $subscription->get_fields() as $field ) {
		$setting = $field->get_setting_from_form();
		$settings[ $field->get( 'name' ) ] = $setting;
	}	
	$subscription->set( 'settings', $settings );
	$subscription->save();

	// Reload subscription to refresh data from Mother, based on new settings.
	$subscription = Subscriptions\get_subscription( $subscription->ID );	
	
	if ( \is_wp_error( $subscription ) ) {
		Admin\Notices\add_error( $subscription );
		Admin\redirect( get_admin_page_url() );
		exit;
	}

	$theater = $subscription->get( 'theater' );
	
	if ( \Jeero\Subscriptions\JEERO_SUBSCRIPTIONS_STATUS_SETUP == $subscription->get( 'status' ) ) {		
		Admin\Notices\add_success( sprintf( __( '%s subscription updated. Please enter any missing settings below.', 'jeero' ), $theater[ 'title' ] ) );
	} else {		
		Admin\Notices\add_success( sprintf( __( '%s subscription updated.', 'jeero' ), $theater[ 'title' ] ) );					
	}
	Admin\redirect( get_admin_edit_url( $subscription->get( 'ID' ) ) );
	
}

/**
 * Gets the HTML for the Edit Subscription Admin page.
 *
 * Builds a form based on the fields of the Subscription.
 * 
 * @since 	1.?
 * @since	1.5		Restructured HTML to add support for tabs.
 * @since	1.16	Save changes button is now translatable. 
 * 
 * @param	int				$subscription_id		The ID of the Subscription.
 * @return 	string|WP_Error						The HTML for the Edit Subscription Admin page.
 *												Or an error if there was a problem.
 */
function get_edit_html( $subscription_id ) {

	$subscription = Subscriptions\get_subscription( $subscription_id );

	if ( is_wp_error( $subscription ) ) {
		Admin\Notices\add_error( $subscription );
		return Admin\redirect( get_admin_page_url() );
	}
	
	ob_start();
	
	?><div class="wrap">
		<h1><?php _e( 'Edit Import', 'jeero' ); ?></h1>
		<form class="jeero-form"><?php
			wp_nonce_field( 'save', 'jeero/nonce', true, true );
			?><input type="hidden" name="subscription_id" value="<?php echo $subscription_id; ?>">
			<table class="form-table">
				<tbody><?php
					foreach( $subscription->get_fields() as $field ) {
						?><tr class="<?php echo implode( ' ', $field->get_css_classes() ); ?>">
							<th scope="row"><?php echo $field->get_label_html(); ?></th>
							<td><?php echo $field->get_control_html(); ?></td>
						</tr><?php
					}

				?></tbody>
			</table>
			<p class="jeero-submit">
				<input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e( 'Save Changes', 'jeero' ); ?>">
				<a href="<?php echo get_admin_page_url(); ?>" class="button"><?php _e( 'Cancel', 'jeero' ); ?></a>
			</p>
		</form>
			
		
	</div><?php
		
	return ob_get_clean();
}

/**
 * Gets the List Table on the Subscriptions Admin page.
 * 
 * @since	1.29
 * @return	WP_List_Table
 */
function get_list_table() {
	
	$list_table = wp_cache_get( 'list_table', __NAMESPACE__ );
	
	if ( false === $list_table) {
		
		$list_table = new List_Table();	
		$list_table->prepare_items();

		wp_cache_set( 'list_table', $list_table, __NAMESPACE__ );
		
	}

	return $list_table;	
}

/**
 * Gets the HTML for the List Table on the Subscriptions Admin page.
 * 
 * @since	1.0
 * @since	1.1		Renamed 'subscriptions' to 'imports'.
 * @since	1.7		Autmatically create first subscription and show edit form.
 *					Replaced 'next pickup' message with 'next import' message.
 * @since	1.29	Only display next import if it is scheduled in the future.
 * @return	string
 */
function get_list_table_html() {
	
	$list_table = get_list_table();	

	if ( empty( $list_table->subscriptions ) ) {
		
		$subscription_id = Subscriptions\add_subscription();

		if ( \is_wp_error( $subscription_id ) ) {
			Admin\Notices\add_error( $subscription_id );
		} else {
			return get_edit_html( $subscription_id );
		}

	}
	
	ob_start();
	
	?><div class="wrap">
		<h1 class="wp-heading-inline"><?php _e( 'Jeero', 'jeero' ); ?></h1>
		<a href="<?php echo get_new_subscription_url();?>" class="page-title-action"><?php _e( 'Add Import', 'jeero' ); ?></a>
		<hr class="wp-header-end"><?php
			
		$list_table->views();
			
		$list_table->display(); 

		$current_time = time();
		$next_import = get_next_import( $list_table->subscriptions );
		
		if ( $current_time < $next_import ) {
	    
		    ?><p title="<?php
			    echo date_i18n( 'd-m-Y H:i:s', $next_import + get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ); 
			?>"><?php 
			    printf( __( 'Next import in %s.', 'jeero' ), human_time_diff( $next_import, time( ) ) );
			?></p><?php
			
		}

	?></div><?php
		
	return ob_get_clean();

}

/**
 * Gets the edit URL for the Edit Subscription Admin page.
 * 
 * @since	1.0
 * @param 	string	$subscription_id
 * @return	string
 */
function get_admin_edit_url( $subscription_id ) {
	return add_query_arg( 'edit', $subscription_id, get_admin_page_url() );
}

/**
 * Gets the HTML for the Subscriptions Admin pages.
 * 
 * @since	1.0
 * @return	string
 */
function get_admin_page_html() {

	// Return Edit Subscription Admin page when editing a subscription. 
	if ( isset( $_GET[ 'edit' ] ) ) {
		return get_edit_html( sanitize_text_field( $_GET[ 'edit' ] ) );
	}
	
	return get_list_table_html( );	
	
}

/**
 * Gets the URL for the Subscriptions Admin page.
 * 
 * @since	1.0
 * @since	1.1	Renamed 'subscriptions' to 'imports'.
 * @return	string
 */
function get_admin_page_url() {
	return admin_url( 'admin.php?page=jeero/imports');
}

/**
 * Gets the URL for creating a new subscription.
 * 
 * @since	1.0
 * @return	string
 */
function get_new_subscription_url() {	
	return wp_nonce_url( get_admin_page_url(), 'add', 'jeero/nonce' );
}

/**
 * Gets the next import timestamp for a list of subscriptions.
 * 
 * @since	1.7
 * @param 	Subscription[]	$subscriptions
 * @return 	int				The next import timestamp in UTC.
 */
function get_next_import( $subscriptions ) {

	$current_time = time();

	$next_import = $current_time + 5 * MINUTE_IN_SECONDS; // Now + 5 minutes in UTC.
	
	foreach ( $subscriptions as $subscription ) {

		$next_delivery = $subscription->get( 'next_delivery' ); // Next delivery in UTC.

		if ( $next_delivery > $next_import )  {
			continue;
		}

		$next_import = $next_delivery;
		
	}

	$next_pickup = Inbox\get_next_pickup();

	if ( $next_pickup < $next_delivery ) {
		// Next delivery is later than next pickup.
		// Find first pickup after next delivery.
		$minutes_next_pickup_after_next_delivery = ceil( ( $next_delivery - $next_pickup ) / MINUTE_IN_SECONDS );
		return $next_pickup + $minutes_next_pickup_after_next_delivery * MINUTE_IN_SECONDS;
		
	}
	
	return $next_pickup;
	
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
		return Admin\redirect( get_admin_page_url() );
	}

	return Admin\redirect( get_admin_edit_url( $subscription_id ) );
	
}

/**
 * Shows an admin notice on Jeero admin sceens if no supported calendar plugins are active.
 * 
 * @since	1.5
 */
function show_no_active_calendars_warning() {

	$screen = get_current_screen();
	
	if ( $screen->id != 'toplevel_page_jeero/imports' ) {
		return;
	}
	
	$active_calendars = Calendars\get_active_calendars();
	
	if ( empty( $active_calendars ) ) {
		?><div class="notice notice-error">
			<p><?php
				_e( 'Please activate at least one supported calendar plugin to start importing events.', 'jeero' );
			?></p>
		</div><?php
	}
	
}

/**
 * Shows an admin error notice on Jeero admin screens if WP-Cron is disabled.
 * 
 * @since	1.29
 * @since	1.29.3	Disabled cron detection now uses next pickup instead of next import. 
 * @since	1.29.4	Disabled cron detection now has some breathing space to prevent false positives 
 *					if the user refreshes the screen before WP was able to schedule the next pickip. 
 */
function show_wp_cron_disabled_error() {
	
	$list_table = get_list_table();

	$current_time = time();
	
	$next_pickup = Inbox\get_next_pickup();
	
	if ( false === $next_pickup ) {
		// No pickup schedules yet.
		return;
	}

	// Cron is disabled if the next pickup is not scheduled in the future (with 10 minutes breathing space).
	if ( $current_time > ( $next_pickup + 10 * MINUTE_IN_SECONDS ) ) {
		?><div class="notice notice-error">
			<p><strong><?php _e( 'Jeero import error: WP-Cron not working', 'jeero' ); ?></strong></p>
			<p><?php _e( 'It looks like WP-Cron, the feature that Jeero relies on to import events from your ticketing system, is currently not functioning properly. This may prevent events from being imported into your WordPress site.', 'jeero' ); ?></p>
			<p><a href="https://jeero.ooo/enable-wp-cron/"><?php _e( 'Please enable WP-Cron', 'jeero' ); ?></a>.</p>
		</div><?php
	}
	
}