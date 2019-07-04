<?php
/**
 * Manages all of Jeero's Subscriptions.
 */
namespace Jeero\Subscriptions;

use Jeero\Mother;

/**
 * Adds a Subscription.
 * 
 * @return	Subscription	The new Subscription.
 */
function add_subscription( ) {
	
	return Mother\subscribe_me();

}

/**
 * Gets all of Jeero's Subscriptions.
 * 
 * @return	Subscription[]	An array containing all of Jeero's Subscriptions.
 */
function get_subscriptions() {
	
	return Mother\get_subscriptions();
		
}

/**
 * Cancels a Subscription.
 * 
 * @param Subscription $subscription
 * @return void
 */
function cancel_subscription( Subscription $subscription ) {
	
}