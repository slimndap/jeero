<?php
namespace Jeero\Migrate;

add_action( 'admin_init', __NAMESPACE__.'\handle_migrate_unknown_settings' );
add_action( 'admin_init', __NAMESPACE__.'\handle_delete_unknown_settings' );
add_action( 'admin_notices', __NAMESPACE__.'\handle_unknown_settings' );

function apply_settings_to_subscription( $subscription_id, $settings ) {

	$subscription = \Jeero\Subscriptions\get_subscription( $subscription_id );
	
	if ( is_wp_error( $subscription ) ) {
		return $subscription;
	}
	
	$subscription->set( 'settings', $settings );
	$subscription->save();
	
	return $subscription;
	
}

function delete_settings( $subscription_id ) {
	\Jeero\Db\Subscriptions\remove_subscription( $subscription_id );	
}

function handle_delete_unknown_settings() {

	if ( empty( $_GET[ 'jeero/nonce' ] ) ) {
		return;
	}

	if ( !wp_verify_nonce( $_GET[ 'jeero/nonce' ], 'delete_unknown_settings' ) ) {
		return;
	}
	
	if ( empty( $_GET[ 'subscription_id' ] ) ) {
		return;
	}
	delete_settings( $_GET[ 'subscription_id' ] );

	\Jeero\Admin\Notices\add_success( 
		__( 'Removed settings from unknown subscription.', 'jeero' )
	);	
	
	wp_safe_redirect( get_admin_page_url() )	;
	exit;
}

function handle_migrate_unknown_settings() {
	
	if ( empty( $_GET[ 'jeero/nonce' ] ) ) {
		return;
	}
	
	if ( !wp_verify_nonce( $_GET[ 'jeero/nonce' ], 'migrate_unknown_settings' ) ) {
		return;
	}
	
	if ( empty( $_GET[ 'subscription_id' ] ) ) {
		return;
	}
	
	$old_subscription_id = sanitize_text_field( $_GET[ 'subscription_id' ] );
	
	$new_subscription_id = \Jeero\Subscriptions\add_subscription();
	
	if ( is_wp_error( $new_subscription_id ) ) {

		\Jeero\Admin\Notices\add_error( $new_subscription_id->get_error_message() );
		return;
		
	}

	$setting_values = \Jeero\Subscriptions\get_setting_values();

	if ( !array_key_exists( $old_subscription_id, $setting_values ) ) {

		\Jeero\Admin\Notices\add_error( new \WP_Error( 'jeero', __( 'Settings not found.', 'jeero' ) ) );
		return;
		
		
	}

	$subscription = apply_settings_to_subscription( $new_subscription_id, $setting_values[ $old_subscription_id ] );

	if ( is_wp_error( $subscription ) ) {

		\Jeero\Admin\Notices\add_error( $subscription->get_error_message() );
		return;
		
	}
	
	if ( empty( $setting_values[ $old_subscription_id ][ 'theater' ] ) ) {
		\Jeero\Admin\Notices\add_success( 
			__( 'New import added with settings from unknown import.', 'jeero' )
		);							
	} else {
		\Jeero\Admin\Notices\add_success( 
			sprintf( 
				__( 'New import added with settings from unknown %s import.', 'jeero' ),
				sprintf( '<strong>%s</strong>', ucfirst( $setting_values[ $old_subscription_id ][ 'theater' ] ) )
			) 
		);					
	}

	delete_settings( $old_subscription_id );
	
	wp_safe_redirect( get_admin_page_url() )	;
	exit;

}

function handle_unknown_settings() {
	
	$screen = get_current_screen();
	
	if ( $screen->id != 'toplevel_page_jeero/imports' ) {
		return;
	}

	$subscriptions = \Jeero\Subscriptions\get_subscriptions();
	$jeero_settings = \Jeero\Db\Subscriptions\get_subscriptions();
	
	$unknown_settings = array();
	
	foreach( $jeero_settings as $subscription_id => $setting ) {
		
		if ( array_key_exists($subscription_id, $subscriptions ) ) {
			continue;
		}
		
		$unknown_settings[ $subscription_id ] = $setting;
	}
	
	if ( empty( $unknown_settings ) ) {
		return;
	}

	foreach( $unknown_settings as $subscription_id => $subscription_data ) {

		$unknown_subscription = new \Jeero\Subscriptions\Subscription( $subscription_id );
		$setting = $unknown_subscription->get( 'settings' );

		ob_start();
		?><p><?php
			if ( empty( $setting[ 'theater' ] ) ) {
				_e( 'Jeero found settings for an unknown import.', 'jeero' );
			} else {
				printf( 
					__( 'Jeero found settings for an unknown %s import.', 'jeero' ),
					sprintf( '<strong>%s</strong>', esc_html( ucwords( $setting[ 'theater' ] ) ) )
				);
			}
			
		?></p>
		<p><?php
			
			$url = add_query_arg( 'subscription_id', $subscription_id, get_admin_page_url() );
			$url = wp_nonce_url( $url, 'migrate_unknown_settings', 'jeero/nonce' );
			
			?><a href="<?php echo $url; ?>" class="button button-primary"><?php
				_e( 'Add import with the same settings', 'jeero' );
			?></a> <?php

			$url = add_query_arg( 'subscription_id', $subscription_id, get_admin_page_url() );
			$url = wp_nonce_url( $url, 'delete_unknown_settings', 'jeero/nonce' );

			?><a href="<?php echo $url; ?>" class="button"><?php
				_e( 'Remove settings', 'jeero' );
			?></a>
		</p><?php
			
		\Jeero\Admin\Notices\add_warning( ob_get_clean() );
		
	}
	
}

function get_admin_page_url() {
	return admin_url( 'admin.php?page=jeero/imports' );
}