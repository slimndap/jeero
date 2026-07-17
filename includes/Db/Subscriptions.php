<?php
/**
 * Manages handling of Subscriptions in the DB.
 */
namespace Jeero\Db\Subscriptions;

const JEERO_OPTION_SUBSCRIPTION = 'jeero_subscriptions';

/**
 * Gets the settings for all Subscriptions.
 * 
 * @since	1.0
 * @return	array
 */
function get_settings() {
	
	$subscriptions = get_subscriptions();

	$settings = array();	
	foreach( $subscriptions as $subscription_id => $subscription ) {
		$settings[ $subscription_id ] = $subscription[ 'settings' ];
	}
	
	return $settings;

}

/**
 * Gets all Subscriptions.
 * 
 * @since	1.0
 * @return	array
 */
function get_subscriptions() {
	
	return get_option( JEERO_OPTION_SUBSCRIPTION, array() );

}

/**
 * Gets a single Subscription.
 * 
 * @since	1.0
 * @param 	string		$subscription_id
 * @return	array|bool						The Subscription or <false> if no Subscription is found.
 */
function get_subscription( $subscription_id ) {
	
	$subscriptions = get_subscriptions();
	
	if ( empty( $subscriptions[ $subscription_id ] ) ) {
		return false;
	}
	
	return $subscriptions[ $subscription_id ];
		
}

/**
 * Saves a Subscription to the DB.
 * 
 * @since	1.0
 * @param 	string	$subscription_id
 * @param 	array	$settings
 * @return 	void
 */
function save_subscription( $subscription_id, $settings ) {

	$subscriptions = get_subscriptions();
	
	$subscriptions[ $subscription_id ] = wp_parse_args(
		array(
			'settings' => $settings,
		),
		$subscriptions[ $subscription_id ] ?? array()
	);
	
	update_option( JEERO_OPTION_SUBSCRIPTION, $subscriptions, false );

}

/**
 * Saves remote subscription state to the local DB cache.
 *
 * @since 1.34
 * @param string $subscription_id Subscription ID.
 * @param array  $state           Remote subscription state.
 * @return void
 */
function save_subscription_state( $subscription_id, array $state ) {

	$subscriptions = get_subscriptions();

	if ( empty( $subscriptions[ $subscription_id ] ) ) {
		$subscriptions[ $subscription_id ] = array(
			'settings' => array(),
		);
	}

	foreach ( array( 'inactive', 'theater' ) as $key ) {
		if ( array_key_exists( $key, $state ) ) {
			$subscriptions[ $subscription_id ][ $key ] = $state[ $key ];
		}
	}

	update_option( JEERO_OPTION_SUBSCRIPTION, $subscriptions, false );

}
