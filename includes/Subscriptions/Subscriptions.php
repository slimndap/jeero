<?php
/**
 * Manages all of Jeero's Subscriptions.
 *
 */
namespace Jeero\Subscriptions;

use Jeero\Db;
use Jeero\Mother;
use Jeero\Calendars;
use Jeero\Account;

const JEERO_SUBSCRIPTIONS_STATUS_SETUP = 'setup';
const JEERO_SUBSCRIPTIONS_STATUS_READY = 'ready';

/**
 * Adds a new Subscription.
 *
 * Asks Mother to setup a new Subscription.
 * 
 * @return	string	The ID of the new Subscription.
 */
function add_subscription( ) {
	
	$answer = Mother\subscribe_me();

	if ( is_wp_error( $answer ) ) {
		return $answer;
	}

	return $answer[ 'id' ];

}


/**
 * Gets the Settings of all Subscriptions.
 * 
 * @since	1.0
 * @return	array
 */
function get_setting_values() {

	$subscriptions = Db\Subscriptions\get_subscriptions();

	$settings = array();	
	foreach( $subscriptions as $subscription_id => $subscription_data ) {
		
		$subscription = new Subscription( $subscription_id );
		$settings[ $subscription_id ] = $subscription->get( 'settings' );
	}
	
	return $settings;	
			
}
/**
 * Gets a Subscription.
 *
 * Gets the Subscription info from Mother and loads the settings from the DB.
 * 
 * @since	1.0
 * @since	1.4		Set a default value for 'interval'.
 * 					Added support for custom calendar fields.
 * @since	1.5		Removed calendar activation checkboxes.
 *					They are now managed by the individual calendars.
 *
 * @param 	int						$subscription_id	The Subscription ID.
 * @return	Subscription|WP_Error	The Subscription. Or an error if something went wrong.
 */
function get_subscription( $subscription_id ) {

	return Mother\get_subscription( $subscription_id );

}

/**
 * Gets all of Jeero's Subscriptions.
 * 
 * Gets the Subscriptions settings from the database and the info from Mother.
 * 
 * @return	Subscription[]	An array containing all of Jeero's Subscriptions.
 */
function get_subscriptions() {

	$setting_values = get_setting_values();

	return Mother\get_subscriptions( $setting_values );
		
}