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
use Jeero\Logs;
use Jeero\Logs\Stats;

const PICKUP_ITEMS_HOOK = 'jeero\inbox\pickup_items';

add_action( 'init', __NAMESPACE__.'\schedule_next_pickup' );
add_action( PICKUP_ITEMS_HOOK, __NAMESPACE__.'\pickup_items' );

/**
 * Applies WordPress filters.
 * 
 * Wrapper for apply_filters() that prevents filters from running if a WP_Error is passed.
 * This can be used stop the filters from running during process_item().
 *
 * @since	1.5
 * @param 	string	$tag
 * @param 	mixed	$value
 * @return	mixed
 */
function apply_filters( $tag, $value ) {
	
	if ( \is_wp_error( $value ) ) {
		return $value;
	}
	
	$args = func_get_args();

	return \apply_filters( ...$args );
	
}

/**
 * Picks up items from Inbox and processes them.
 * 
 * @since	1.0
 * @since	1.18	@uses \Jeero\Logs\log().
 * @since	1.27	@uses get_inbox_no_of_items_per_pickup() to set the number of items in each inbox pickup. 
 * @since	1.30	Added stats.
 * @return 	void
 */
function pickup_items() {

	$no_of_items_per_pickup = get_inbox_no_of_items_per_pickup();

	if ( $no_of_items_per_pickup ) {
		Logs\Log( 
			sprintf( 
				_n( 'Pick up %d item from inbox.', 'Pick up %d items from inbox.', $no_of_items_per_pickup ),
				$no_of_items_per_pickup
			)
		);
	} else {
		Logs\Log( 'Pick up items from inbox.' );			
	}

	$settings = Subscriptions\get_setting_values();

	$items = Mother\get_inbox( $settings, $no_of_items_per_pickup );
	
	if ( is_wp_error( $items ) ) {
		Admin\Notices\add_error( $items );
		return;
	}
	
	if ( empty( $items ) ) {
		Logs\Log( 'No items found in inbox.' );		
		Logs\Stats\add_stat( 'items_picked_up', 0 );
	} else {
		$items_found = count( $items );
		Logs\Log( sprintf( '%d items found in inbox.', $items_found ) );
		Logs\Stats\add_stat( 'items_picked_up', $items_found );
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
 * Gets the number of items in each inbox pickup.
 * 
 * @since	1.27
 * @return	int|null	A number or null, which tells Jeero to use the default number of items.
 */
function get_inbox_no_of_items_per_pickup() {

	return \apply_filters( 'jeero/inbox/no_of_items_per_pickup', null );
	
}

/**
 * Gets the number of seconds between each inbox pickup.
 * 
 * @since	1.27.2
 * @return	int	The number of seconds.
 */
function get_inbox_pickup_interval() {

	return \apply_filters( 'jeero/inbox/pickup_interval', MINUTE_IN_SECONDS );
	
}

/**
 * Processes a single item from the Inbox.
 * 
 * @since	1.0
 * @since	1.4		Added the subscription to all filter params.
 * @since	1.5		Flipped order of the calendar filters. The most specific filter now runs first.
 *					Now uses the local apply_filters() wrapper function. 
 *
 * @param 	array	$item
 * @return 	void
 */
function process_item( $item ) {
	
	$action = $item[ 'action' ];
	$theater = $item[ 'theater' ];

	$subscription = new Subscriptions\Subscription( $item[ 'subscription_id' ] );
	
	$result = true;
	
	$calendars = $subscription->get_setting( 'calendar' );
	
	if ( !empty( $calendars ) ) {

		foreach( $calendars as $calendar ) {
			
			/**
			 * Filters the result of a processed inbox item.
			 * 
			 * Only runs for specific action/theater/calendar combinations.
			 * This filter runs before events are processed Calendar importers.
			 *
			 * @since 1.0
			 *
			 * @param 	bool|WP_Error	$result			The result of a processed inbox item. 
			 *											Filters will not be applied if $result is a WP_Error.
			 * @param	array			$data			The structured data of the inbox item.
			 * @param	mixed			$raw				The raw data of the inbox item.
			 *											Usually coming from the Theater API.
			 * @param	Subscription		$subscription	The susbcription.
			 */
			$result = apply_filters(
				'jeero/inbox/process/item/'.$action.'/theater='.$theater.'&calendar='.$calendar, 
				$result, 
				$item[ 'data' ], 
				$item[ 'raw' ],
				$subscription
			);
			
			/**
			 * Filters the result of a processed inbox item.
			 * 
			 * Only runs for specific action/calendar combinations.
			 * This filter is used by Calendar importers to process events (with priority 10).
			 *
			 * @since 1.0
			 *
			 * @param 	bool|WP_Error	$result			The result of a processed inbox item. 
			 *											Filters will not be applied if $result is a WP_Error.
			 * @param	array			$data			The structured data of the inbox item.
			 * @param	mixed			$raw				The raw data of the inbox item.
			 *											Usually coming from the Theater API.
			 * @param	string			$theater			The theater.
			 * @param	Subscription		$subscription	The susbcription.
			 */
			$result = apply_filters( 
				'jeero/inbox/process/item/'.$action.'/calendar='.$calendar, 
				$result,
				$item[ 'data' ], 
				$item[ 'raw' ],
				$theater,
				$subscription
			);
			
		}
		
	}
	
	/**
	 * Filters the result of a processed inbox item.
	 * 
	 * Only runs for specific action/theater combinations.
	 * This filter runs after events are processed Calendar importers.
	 *
	 * @since 1.0
	 *
	 * @param 	bool|WP_Error	$result			The result of a processed inbox item. 
	 *											Filters will not be applied if $result is a WP_Error.
	 * @param	array			$data			The structured data of the inbox item.
	 * @param	mixed			$raw				The raw data of the inbox item.
	 *											Usually coming from the Theater API.
	 * @param	Subscription		$subscription	The susbcription.
	 */
	$result = apply_filters( 
		'jeero/inbox/process/item/'.$action.'/theater='.$theater, 
		$result,
		$item[ 'data' ], 
		$item[ 'raw' ],
		$subscription
	);

	/**
	 * Filters the result of a processed inbox item.
	 * 
	 * Only runs for specific actions.
	 * This filter runs after events are processed Calendar importers.
	 *
	 * @since 1.0
	 *
	 * @param 	bool|WP_Error	$result			The result of a processed inbox item. 
	 *											Filters will not be applied if $result is a WP_Error.
	 * @param	array			$data			The structured data of the inbox item.
	 * @param	mixed			$raw			The raw data of the inbox item.
	 *											Usually coming from the Theater API.
	 * @param	string			$theater			The theater.
	 * @param	Subscription		$subscription	The susbcription.
	 */
	$result = apply_filters(
		'jeero/inbox/process/item/'.$action, 
		$result, 
		$item[ 'data' ], 
		$item[ 'raw' ],
		$theater,
		$subscription
	);
	
	/**
	 * Filters the result of a processed inbox item.
	 * 
	 * This filter runs after events are processed Calendar importers.
	 *
	 * @since 1.0
	 *
	 * @param 	bool|WP_Error	$result			The result of a processed inbox item. 
	 *											Filters will not be applied if $result is a WP_Error.
	 * @param	array			$data			The structured data of the inbox item.
	 * @param	mixed			$raw			The raw data of the inbox item.
	 *											Usually coming from the Theater API.
	 * @param	string			$action			The action performed on the inbox item.
	 * @param	string			$theater			The theater.
	 * @param	Subscription		$subscription	The susbcription.
	 */
	$result = apply_filters(
		'jeero/inbox/process/item', 
		$result, 
		$item[ 'data' ], 
		$item[ 'raw' ],
		$action,
		$theater,
		$subscription
	);
	
	return $result;
	
}

/**
 * Processes all items in Inbox and removes processed items from Inbox.
 * 
 * @since	1.0
 * @since	1.5		Now accounts for process_item() returning a WP_Error.
 * @since	1.18	@uses \Jeero\Logs\log().
 * @since	1.27.1	Remove inbox items before processing them, to avoid processing inbox items multiple times.
 *					Added time elapsed to log message.
 * @since	1.27.2	Remove inbox items after processing.
 *					@uses get_inbox_pickup_interval() to stop processing items before the next pick up begins. 
 * @since	1.30	Added stats.
 * @since	1.30.1	Added stats for created and updated events. 
 *
 * @param 	array	$items
 * @return 	void
 */
function process_items( $items ) {
	
	if ( empty( $items ) ) {
		return;
	}
	
	$start_time = microtime( true );
	$pickup_interval = get_inbox_pickup_interval();
	
	$items_processed = array();
	
	foreach( $items as $item ) {
		$result = process_item( $item );
		
		if ( \is_wp_error( $result ) ) {
			Logs\Log( $result->get_error_message() );
		}
		
		$items_processed[] = $item;
		
		$elapsed_time = microtime( true ) - $start_time;
		
		if ( $elapsed_time > ( $pickup_interval - 1 ) ) {
			Logs\Log( sprintf( 'Stopped processing items after %.2f seconds to prevent overlap with next pickup.', $elapsed_time ) );
			break;
		}
		
	}
	
	remove_items( $items_processed );

	$elapsed_time = microtime( true ) - $start_time;
	
	Logs\Log( sprintf( 'Processed %d out of %d items in %.2f seconds.', count( $items_processed ), count( $items ), $elapsed_time ) );

	Logs\Stats\add_stat( 'items_processed', count( $items_processed ) );
	Logs\Stats\add_stat( 'processing_time', $elapsed_time );

	Logs\Stats\add_stat( 'events_created', Logs\Stats\cache_get( 'events_created' ) );
	Logs\Stats\add_stat( 'events_updated', Logs\Stats\cache_get( 'events_updated' ) );
	
}

/**
 * Removes items from the Inbox.
 * 
 * @since	1.0
 * @since	1.18	@uses \Jeero\Logs\log().
 *
 * @param 	array $items
 * @return	array|WP_Error
 */
function remove_items( array $items ) {
	
	Logs\Log( sprintf( 'Removing %d items from Inbox.', count( $items ) ) );

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
	\wp_schedule_single_event( time() + get_inbox_pickup_interval(), PICKUP_ITEMS_HOOK );
	
}