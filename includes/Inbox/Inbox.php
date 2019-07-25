<?php
/**
 * Handles the Inbox.
 *
 * Steps: 
 * 1. Jeero asks Mother to check the Inbox.
 * 2. Mother returns items from Inbox.
 * 3. Jeero moves items into the Queue. 
 * 4. Jeero plans to ask Mother again in a minute.
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
	
	// Schedule the next pick up.
	schedule_next_pickup();
	
	// Move items to Queue.
	
	return $inbox;
	
}

/**
 * Gets the timestamp of the next scheduled pick up.
 * 
 * @since	1.0
 * @return	int
 */
function get_next_pickup() {
	
	return wp_next_scheduled( PICKUP_ITEMS_HOOK );
	
}

/**
 * Schedules the next pick up.
 * 
 * @since	1.0
 * @return 	void
 */
function schedule_next_pickup() {
	
	// Remove any previously scheduled pickups.
	wp_clear_scheduled_hook( PICKUP_ITEMS_HOOK );
	
	// Ask Mother to check again in a minute.
	wp_schedule_single_event( time() + MINUTE_IN_SECONDS, PICKUP_ITEMS_HOOK );
	
}