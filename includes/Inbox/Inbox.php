<?php
/**
 * Handles the Inbox.
 *
 * @since	1.0
 *
 * Steps: 
 * 1. Jeero asks Mother to check the Inbox.
 * 2. Mother returns items from Inbox.
 * 3. Jeero processes items. 
 * 4. Jeero ask Mother to remove processes items from Inbox.
 * 5. Jeero plans to ask Mother again in a minute.
 */
namespace Jeero\Inbox;

use Jeero\Mother;
use Jeero\Admin;
use Jeero\Db;
use Jeero\Subscriptions;

const PICKUP_ITEMS_HOOK = 'jeero\inbox\pickup_items';

add_action( 'init', __NAMESPACE__.'\schedule_next_pickup' );
add_action( PICKUP_ITEMS_HOOK, __NAMESPACE__.'\pickup_items' );

/**
 * Picks up items from Inbox and processes them.
 * 
 * @since	1.0
 * @return 	void
 */
function pickup_items() {

	error_log( 'Pick up items from inbox.' );

	$settings = Subscriptions\get_setting_values();

	$items = Mother\get_inbox( $settings );
	
	if ( is_wp_error( $items ) ) {
		Admin\Notices\add_error( $items );
		return;
	}
	
	if ( empty( $items ) ) {
		error_log( 'No items found in inbox.' );		
	} else {
		error_log( sprintf( '%d items found in inbox.', count( $items ) ) );
	}
		
	process_items( $items );
	
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
 * Processes a single item from the Inbox.
 * 
 * @since	1.0
 * @since	1.4		Added the subscription to all filter params.
 *
 * @param 	array	$item
 * @return 	void
 */
function process_item( $item ) {
	
	$action = $item[ 'action' ];
	$theater = $item[ 'theater' ];

	$subscription = new Subscriptions\Subscription( $item[ 'subscription_id' ] );
	
	$result = false;
	
	$calendars = $subscription->get_setting( 'calendar' );
	
	if ( !empty( $calendars ) ) {

		foreach( $calendars as $calendar ) {
			
			$result = apply_filters( 
				'jeero/inbox/process/item/'.$action.'/calendar='.$calendar, 
				$result,
				$item[ 'data' ], 
				$item[ 'raw' ],
				$theater,
				$subscription
			);
			
			$result = apply_filters(
				'jeero/inbox/process/item/'.$action.'/theater='.$theater.'&calendar='.$calendar, 
				$result, 
				$item[ 'data' ], 
				$item[ 'raw' ],
				$subscription
			);
			
		}
		
	}
	
	$result = apply_filters( 
		'jeero/inbox/process/item/'.$action.'/theater='.$theater, 
		$result,
		$item[ 'data' ], 
		$item[ 'raw' ],
		$subscription
	);

	$result = apply_filters(
		'jeero/inbox/process/item/'.$action, 
		$result, 
		$item[ 'data' ], 
		$item[ 'raw' ],
		$theater,
		$subscription
	);
	
	$result = apply_filters(
		'jeero/inbox/process/item', 
		$result, 
		$item[ 'data' ], 
		$item[ 'raw' ],
		$action,
		$theater,
		$subscription
	);
	
}

/**
 * Processes all items in Inbox and removes processed items from Inbox.
 * 
 * @since	1.0
 * @param 	array	$items
 * @return 	void
 */
function process_items( $items ) {
	
	if ( empty( $items ) ) {
		return;
	}
	
	$items_processed = array();
	
	foreach( $items as $item ) {
		process_item( $item );
		$items_processed[] = $item;
	}
	
	error_log( sprintf( '%d items processed.', count( $items_processed ) ) );
	
	remove_items( $items_processed );
}

/**
 * Removes items from the Inbox.
 * 
 * @since	1.0
 * @param 	array $items
 * @return	array|WP_Error
 */
function remove_items( array $items ) {
	
	error_log( sprintf( 'Removing %d items from Inbox.', count( $items ) ) );

	$item_ids = wp_list_pluck( $items, 'ID' );
	return Mother\remove_inbox_items( $item_ids );
	
}

/**
 * Schedules the next pick up.
 * 
 * @since	1.0
 * @return 	void
 */
function schedule_next_pickup() {

	$next_pickup = get_next_pickup();
	
	// Bail if pickup is already scheduled.
	if ( $next_pickup ) {
		return;
	}
	
	// Ask Mother to check again in a minute.
	\wp_schedule_single_event( time() + MINUTE_IN_SECONDS, PICKUP_ITEMS_HOOK );
	
}