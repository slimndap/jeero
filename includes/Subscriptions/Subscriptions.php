<?php
/**
 * Manages all of Jeero's Subscriptions.
 *
 */
namespace Jeero\Subscriptions;

use Jeero\Db;
use Jeero\Mother;

const JEERO_SUBSCRIPTIONS_STATUS_SETUP = 'setup';
const JEERO_SUBSCRIPTIONS_STATUS_READY = 'ready';

/**
 * Adds a Subscription.
 *
 * Asks Mother to setup a new Subscription.
 * 
 * @return	Subscription	The new Subscription.
 */
function add_subscription( ) {
	
	$answer = Mother\subscribe_me();

	if ( is_wp_error( $answer ) ) {
		return $answer;
	}

	return $answer[ 'ID' ];

}

/**
 * Gets a Subscription.
 *
 * Gets the Subscription info from Mother and loads the settings from the DB.
 * 
 * @since	1.0
 * @param 	int						$subscription_id	The Subscription ID.
 * @return	Subscription|WP_Error	The Subscription. Or an error if something went wrong.
 */
function get_subscription( $subscription_id ) {

	// Get the settings from the DB.
	$subscription = new \Jeero\Subscriptions\Subscription( $subscription_id );
	
	// Ask Mother for subscription info, based on the current settings.
	$answer = Mother\get_subscription( $subscription_id, $subscription->get( 'settings' ) );
	if ( is_wp_error( $answer ) ) {
		return $answer;
	}
	
	$defaults = array(
		'status' => false,
		'logo' => false,
		'fields' => array(),
	);
	
	$answer = wp_parse_args( $answer, $defaults );
	
	// Add the subscription info to the Subscription.
	$subscription->set( 'status', $answer[ 'status' ] );
	$subscription->set( 'logo', $answer[ 'logo' ] );
	$subscription->set( 'fields', $answer[ 'fields' ] );

	return $subscription;
}

/**
 * Gets all of Jeero's Subscriptions.
 * 
 * Gets the Subscriptions settings from the database and the info from Mother.
 * 
 * @return	Subscription[]	An array containing all of Jeero's Subscriptions.
 */
function get_subscriptions() {

	$settings = Db\Subscriptions\get_settings();

	// Ask Mother for a list of up-to-date subscriptions.
	$answers = Mother\get_subscriptions( $settings );

	// Update the Subscriptions in the DB.
	$subscriptions = array();
	foreach( $answers as $answer ) {

		$defaults = array(
			'status' => false,
			'logo' => false,
			'fields' => array(),
		);
		
		$answer = wp_parse_args( $answer, $defaults );

		$subscription = new Subscription( $answer[ 'ID' ] );
		$subscription->set( 'status', $answer[ 'status' ] );
		$subscription->set( 'logo', $answer[ 'logo' ] );
		$subscription->set( 'fields', $answer[ 'fields' ] );

		$subscriptions[ $subscription->get( 'ID' ) ] = $subscription;
	}
	
	return $subscriptions;	
		
}