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
	
	$subscriptions[ $subscription_id ] = array(
		'settings' => $settings,
	);
	
	update_option( JEERO_OPTION_SUBSCRIPTION, $subscriptions, false );
		
}
