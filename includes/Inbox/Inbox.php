<?php
/**
 * Handles the inbox.
 *
 * Steps: 
 * 1. User creates/updates the settings of a Subscription.
 * 2. Jeero picks up items from Inbox is Subscription status is 'ready'.
 * 3. Jeero checks schedule for next delivery and plans the next pick up. 
 * 4. Jeero moves items from Inbox into the Queue.
 */
namespace Jeero\Inbox;

use Jeero\Mother;
use Jeero\Db;
use Jeero\Subscriptions;

const PICKUP_ITEMS_HOOK = 'jeero\inbox\pickup_items';

add_action( PICKUP_ITEMS_HOOK, __NAMESPACE__.'\pickup_items' );

/**
 * Picks up items from Inbox and schedule the next pick up.
 * 
 * @since	1.0
 * @return 	void
 */
function pickup_items() {
	
	$inbox = Mother\get_inbox();
	
	// Check and save schedule for next delivery.
	foreach( $inbox[ 'schedule' ] as $ID => $schedule ) {
		$subscription = new Subscriptions\Subscription( $ID );
		$subscription->set( 'next_delivery', $schedule[ 'next_delivery' ] );
		$subscription->save();
	}
	
	// Schedule the next pick up.
	schedule_next_pickup();
	
	// Move items to Queue.
	
	return $inbox[ 'items' ];
	
}

/**
 * Gets the timestamp of the next delivery.
 *
 * Based of the value of the next delivery of all Subscriptions.
 * 
 * @since	1.0
 * @return	int
 */
function get_next_delivery() {
	
	$subscriptions = Db\Subscriptions\get_subscriptions();
	
	if ( empty( $subscriptions ) ) {
		return false;	
	}
	
	$next_delivery = min( wp_list_pluck( $subscriptions, 'next_delivery' ) );
	
	return $next_delivery;
	
}

/**
 * Gets the timestamp of the next schedule pick up..
 * 
 * @since	1.0
 * @return	int
 */
function get_next_pickup() {
	
	return wp_next_scheduled( PICKUP_ITEMS_HOOK );
	
}

/**
 * Schedule the next pick up, based on first upcoming delivery.
 * 
 * @since	1.0
 * @return 	void
 */
function schedule_next_pickup() {
	
	// Remove any previously scheduled pickups.
	wp_clear_scheduled_hook( PICKUP_ITEMS_HOOK );
	
	$next_delivery = get_next_delivery();

	if ( empty( $next_delivery ) ) {
		return;
	}
	
	// Schedule next pickup, based on first upcoming delivery.
	wp_schedule_single_event( $next_delivery, PICKUP_ITEMS_HOOK );
	
}