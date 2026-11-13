<?php
/**
 * Validates, redacts and combines event data from Jeero calendars.
 *
 * @since	1.?
 */
namespace Jeero\Abilities\Data;

use Jeero\Db\Subscriptions;
use Jeero\Calendars;

if ( function_exists( 'wp_register_ability' ) ) {
	add_filter( 'jeero/inbox/process/item', __NAMESPACE__ . '\\capture', PHP_INT_MAX, 6 );
}

/**
 * Redacts credentials from strings and nested arrays.
 *
 * @since	1.?
 *
 * @param	mixed	$value	The value to redact.
 * @return	mixed	The value with known credentials and sensitive fields redacted.
 */
function clean( $value ) {
	$secrets = array();
	$remember = function( $settings ) use ( &$remember, &$secrets ) {
		foreach ( $settings as $name => $setting ) {
			if ( in_array( $name, array( 'theater', 'timezone', 'calendar' ), true ) || false !== strpos( (string) $name, '/import/' ) ) {
				continue;
			}
			if ( is_array( $setting ) ) {
				$remember( $setting );
			} elseif ( is_string( $setting ) && '' !== $setting ) {
				$secrets[] = $setting;
			}
		}
	};
	foreach ( Subscriptions\get_subscriptions() as $id => $subscription ) {
		$remember( $subscription['settings'] ?? array() );
		$runtime = new \Jeero\Subscriptions\Subscription( (string) $id );
		$remember( $runtime->get_settings() );
	}
	$site_key = get_option( \Jeero\Mother\SITE_KEY );
	if ( is_string( $site_key ) && '' !== $site_key ) {
		$secrets[] = $site_key;
	}
	foreach ( $secrets as $secret ) {
		$secrets = array_merge( $secrets, array( rawurlencode( $secret ), urlencode( $secret ), base64_encode( $secret ), substr( wp_json_encode( $secret ), 1, -1 ) ) );
	}
	$secrets = array_unique( $secrets );
	usort( $secrets, function( $a, $b ) { return strlen( $b ) - strlen( $a ); } );
	$redact = function( $item ) use ( &$redact, $secrets ) {
		if ( is_array( $item ) ) {
			$out = array();
			foreach ( $item as $key => $entry ) {
				$safe_key = is_string( $key ) ? str_replace( $secrets, '[REDACTED]', $key ) : $key;
				$out[ $safe_key ] = preg_match( '/api.?key|password|secret|token|authorization|credential|site_key/i', (string) $key )
					? '[REDACTED]' : $redact( $entry );
			}
			return $out;
		}
		if ( ! is_string( $item ) ) {
			return $item;
		}
		$item = str_replace( $secrets, '[REDACTED]', $item );
		$item = preg_replace( '/((?:api[_-]?key|password|secret|(?:access[_-]?|refresh[_-]?)?token|site[_-]?key)["\']?\s*[:=]\s*["\']?)[^&\s"\'<> ,}]+/i', '$1[REDACTED]', $item );
		return preg_replace( '/\b(Bearer|Basic)\s+[A-Za-z0-9+\/=._-]+/i', '$1 [REDACTED]', $item );
	};
	return $redact( $value );
}

/**
 * Stores redacted inbox log messages for the abilities API.
 *
 * @since	1.?
 *
 * @param	mixed                            	$result      	The result of previous inbox processing.
 * @param	array                            	$data        	The structured inbox data.
 * @param	mixed                            	$raw         	The raw connector response.
 * @param	string                           	$action      	The inbox action.
 * @param	string                           	$theater     	The ticketing system name.
 * @param	\Jeero\Subscriptions\Subscription	$subscription	The subscription.
 * @return	mixed	The original processing result.
 */
function capture( $result, $data, $raw, $action, $theater, $subscription ) {
	if ( is_wp_error( $result ) || ! is_array( $data ) ) {
		return $result;
	}
	if ( 'log' === $action && isset( $data['message'] ) && is_string( $data['message'] ) ) {
		$logs = get_option( 'jeero/abilities/logs', array() );
		$logs[] = array(
			'subscription_id' => $subscription->ID,
			'message' => clean( $data['message'] ),
			'updated_at' => current_time( 'mysql', true ),
		);
		// shortcut: keep the latest 1000 inbox logs; use a table if a longer log archive is needed.
		update_option( 'jeero/abilities/logs', array_slice( $logs, -1000 ), false );
		return $result;
	}
	return $result;
}

/**
 * Validates and redacts normalized event fields before storage or output.
 *
 * @since	1.?
 *
 * @param	mixed	$data	The structured event data to validate.
 * @return	array|\WP_Error	The supported event fields, or a validation error.
 */
function prepare_event( $data ) {
	if ( ! is_array( $data ) || ! isset( $data['ref'], $data['start'], $data['production']['title'] ) ) {
		return new \WP_Error( 'jeero_invalid_event', 'Incomplete Jeero event data.' );
	}
	if ( ( ! is_string( $data['ref'] ) && ! is_int( $data['ref'] ) ) || '' === (string) $data['ref'] || strlen( (string) $data['ref'] ) > 191 || ! is_string( $data['start'] ) || '' === trim( $data['start'] ) || ! is_string( $data['production']['title'] ) ) {
		return new \WP_Error( 'jeero_invalid_event', 'Invalid Jeero event identifier or start date.' );
	}
	try {
		$start = new \DateTimeImmutable( $data['start'], wp_timezone() );
		$errors = \DateTimeImmutable::getLastErrors();
		if ( $errors && ( $errors['warning_count'] || $errors['error_count'] ) ) {
			return new \WP_Error( 'jeero_invalid_event', 'Invalid Jeero event start date.' );
		}
	} catch ( \Exception $e ) {
		return new \WP_Error( 'jeero_invalid_event', 'Invalid Jeero event start date.' );
	}
	$categories = $data['production']['categories'] ?? array();
	if ( ! is_array( $categories ) || count( array_filter( $categories, 'is_string' ) ) !== count( $categories ) ) {
		return new \WP_Error( 'jeero_invalid_event', 'Invalid Jeero event categories.' );
	}
	// Preserve normalized source fields, never the raw connector response.
	return clean( array_intersect_key( $data, array_flip( array( 'ref', 'start', 'end', 'status', 'tickets', 'tickets_url', 'prices', 'venue', 'production', 'custom' ) ) ) );
}

/**
 * Validates calendar dates and the order of an event date range.
 *
 * @since	1.?
 *
 * @param	array	$input	The ability input or event filters.
 * @return	true|\WP_Error	True for valid filters, or a date validation error.
 */
function validate_filters( $input ) {
	foreach ( array( 'date', 'from', 'to' ) as $field ) {
		if ( isset( $input[ $field ] ) && ( ! preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $input[ $field ], $parts ) || ! checkdate( (int) $parts[2], (int) $parts[3], (int) $parts[1] ) ) ) {
			return new \WP_Error( 'jeero_invalid_date', 'Use a valid date in YYYY-MM-DD format.' );
		}
	}
	if ( isset( $input['from'], $input['to'] ) && $input['from'] > $input['to'] ) {
		return new \WP_Error( 'jeero_invalid_date', 'The from date must not be after the to date.' );
	}
	return true;
}

/**
 * Checks whether an event matches subscription, text, category and date filters.
 *
 * @since	1.?
 *
 * @param	array	$event	The normalized event data.
 * @param	array	$input	The ability input or event filters.
 * @return	bool	Whether the event matches every supplied filter.
 */
function matches( $event, $input ) {
	foreach ( array( 'subscription_id', 'theater', 'status', 'ref' ) as $field ) {
		if ( isset( $input[ $field ] ) && (string) ( $event[ $field ] ?? '' ) !== (string) $input[ $field ] ) {
			return false;
		}
	}
	if ( isset( $input['category'] ) && ! in_array( $input['category'], $event['production']['categories'] ?? array(), true ) ) {
		return false;
	}
	if ( isset( $input['query'] ) && false === stripos( remove_accents( $event['production']['title'] ), remove_accents( $input['query'] ) ) ) {
		return false;
	}
	if ( isset( $input['date'] ) || isset( $input['from'] ) || isset( $input['to'] ) ) {
		$date = ( new \DateTimeImmutable( $event['start'], wp_timezone() ) )->setTimezone( wp_timezone() )->format( 'Y-m-d' );
		if ( ( isset( $input['date'] ) && $date !== $input['date'] ) || ( isset( $input['from'] ) && $date < $input['from'] ) || ( isset( $input['to'] ) && $date > $input['to'] ) ) {
			return false;
		}
	}
	return true;
}

/**
 * Deduplicates calendar copies by subscription and event reference.
 *
 * @since	1.?
 *
 * @param	array[]	$events	The normalized event data from one or more calendars.
 * @return	array[]	The most recently updated copy of each event.
 */
function unique_events( $events ) {
	$unique = array();
	foreach ( $events as $event ) {
		$key = wp_json_encode( array( $event['subscription_id'], (string) $event['ref'] ) );
		if ( ! isset( $unique[ $key ] ) || $event['updated_at'] > $unique[ $key ]['updated_at'] ) {
			$unique[ $key ] = $event;
		}
	}
	return array_values( $unique );
}

/**
 * Gets unique imported events from calendars and applies the supplied filters.
 *
 * @since	1.?
 *
 * @param	array	$input	The ability input or event filters.
 * @return	array[]|\WP_Error	The matching events, or a calendar read error.
 */
function calendar_events( $input ) {
	$valid = validate_filters( $input );
	if ( is_wp_error( $valid ) ) {
		return $valid;
	}
	$events = array();
	foreach ( Calendars\get_calendars() as $calendar ) {
		if ( ! $calendar instanceof Calendars\Post_Based_Calendar ) {
			continue;
		}
		$items = $calendar->get_events();
		if ( is_wp_error( $items ) ) {
			return $items;
		}
		$events = array_merge( $events, $items );
	}
	return array_values( array_filter( unique_events( $events ), function( $event ) use ( $input ) {
		return matches( $event, $input );
	} ) );
}

/**
 * Gets matching events ordered by start time with pagination metadata.
 *
 * @since	1.?
 *
 * @param	array	$input	The ability input or event filters.
 * @return	array|\WP_Error	The event page and pagination metadata, or a read error.
 */
function get_events( $input ) {
	$events = calendar_events( $input );
	if ( is_wp_error( $events ) ) {
		return $events;
	}
	usort( $events, function( $a, $b ) {
		$start_a = ( new \DateTimeImmutable( $a['start'], wp_timezone() ) )->getTimestamp();
		$start_b = ( new \DateTimeImmutable( $b['start'], wp_timezone() ) )->getTimestamp();
		return $start_a <=> $start_b ?: strcmp( $a['subscription_id'], $b['subscription_id'] ) ?: strcmp( (string) $a['ref'], (string) $b['ref'] );
	} );
	$page = $input['page'] ?? 1;
	$limit = $input['limit'] ?? 50;
	$total = count( $events );
	$events = array_slice( $events, ( $page - 1 ) * $limit, $limit );
	return array(
		'count' => count( $events ), 'total' => $total, 'page' => $page, 'limit' => $limit,
		'totalPages' => (int) ceil( $total / $limit ),
		'hasNextPage' => $page * $limit < $total, 'hasPreviousPage' => $page > 1,
		'events' => $events,
	);
}

/**
 * Gets one imported event by reference and optional subscription.
 *
 * @since	1.?
 *
 * @param	array	$input	The ability input or event filters.
 * @return	array|\WP_Error	The event data, or an error if no unique event is found.
 */
function get_event( $input ) {
	$events = array();
	foreach ( Calendars\get_calendars() as $calendar ) {
		if ( ! $calendar instanceof Calendars\Post_Based_Calendar ) {
			continue;
		}
		$event = $calendar->get_event( $input['ref'], $input );
		if ( is_wp_error( $event ) ) {
			return $event;
		}
		if ( null !== $event ) {
			$events[] = $event;
		}
	}
	$events = unique_events( $events );
	if ( 1 !== count( $events ) ) {
		return new \WP_Error( 'jeero_event_not_unique', 'No unique event found. Check the ref and supply a subscription when needed.' );
	}
	return $events[0];
}

/**
 * Counts unique imported events per category.
 *
 * @since	1.?
 *
 * @param	array[]	$events	The normalized event data.
 * @return	array	The number of categories and the event count for each category.
 */
function categories_from_events( $events ) {
	$counts = array();
	foreach ( unique_events( $events ) as $event ) {
		foreach ( array_unique( $event['production']['categories'] ?? array() ) as $category ) {
			$counts[ $category ] = ( $counts[ $category ] ?? 0 ) + 1;
		}
	}
	ksort( $counts, SORT_NATURAL | SORT_FLAG_CASE );
	$categories = array();
	foreach ( $counts as $name => $count ) {
		$categories[] = array( 'name' => (string) $name, 'events' => $count );
	}
	return array( 'count' => count( $categories ), 'categories' => $categories );
}

/**
 * Gets category counts from imported events matching the supplied filters.
 *
 * @since	1.?
 *
 * @param	array	$input	The ability input or event filters.
 * @return	array|\WP_Error	The category counts, or a calendar read error.
 */
function get_categories( $input ) {
	$events = calendar_events( $input );
	return is_wp_error( $events ) ? $events : categories_from_events( $events );
}
