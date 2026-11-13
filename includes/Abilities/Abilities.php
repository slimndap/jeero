<?php
/**
 * Registers Jeero operations for PHP, REST and the WordPress MCP Adapter.
 *
 * @since	1.?
 */
namespace Jeero\Abilities;

use Jeero\Db\Subscriptions as Db;
use Jeero\Subscriptions;
use Jeero\Mother;
use Jeero\Inbox;

add_action( 'wp_abilities_api_categories_init', __NAMESPACE__ . '\\register_category' );
add_action( 'wp_abilities_api_init', __NAMESPACE__ . '\\register_abilities' );

/**
 * Registers the Jeero ability category.
 *
 * @since	1.?
 *
 * @return	void
 */
function register_category() {
	wp_register_ability_category( 'jeero', array(
		'label' => __( 'Jeero', 'jeero' ),
		'description' => __( 'Jeero subscriptions, event data and imports.', 'jeero' ),
	) );
}

/**
 * Checks whether the current user can execute a Jeero ability.
 *
 * @since	1.?
 *
 * @param	string	$operation	The ability name without the Jeero namespace.
 * @return	bool	Whether the required WordPress capability is granted.
 */
function can_execute( $operation ) {
	$capability = in_array( $operation, array( 'get-events', 'get-event', 'list-event-categories' ), true ) ? 'edit_posts' : 'manage_options';
	return current_user_can( $capability );
}

/**
 * Registers Jeero abilities with their schemas, permissions and MCP metadata.
 *
 * @since	1.?
 *
 * @return	void
 */
function register_abilities() {
	$string = array( 'type' => 'string', 'minLength' => 1, 'maxLength' => 191 );
	$subscription = array( 'subscription' => $string + array( 'description' => 'A subscription ID or its local subscription_key.' ) );
	$theater = array( 'theater' => $string );
	$limit = array( 'limit' => array( 'type' => 'integer', 'minimum' => 1, 'maximum' => 1000 ) );
	$settings = array( 'settings' => array(
		'type' => 'object', 'additionalProperties' => false,
		'properties' => $theater,
	) );
	$dates = array_fill_keys( array( 'date', 'from', 'to' ), array( 'type' => 'string', 'pattern' => '^\\d{4}-\\d{2}-\\d{2}$' ) );
	$filters = $subscription + $theater + $dates + array( 'query' => $string );
	$integer = array( 'type' => 'integer' );
	$boolean = array( 'type' => 'boolean' );
	$summary = array( 'type' => 'object', 'properties' => array(
		'subscription_id' => $string, 'subscription_key' => $string, 'label' => $string,
		'theater' => array( 'type' => 'string' ), 'is_default' => $boolean, 'is_active' => $boolean,
	), 'required' => array( 'subscription_id', 'subscription_key', 'label', 'theater', 'is_default', 'is_active' ) );
	$event = array( 'type' => 'object', 'properties' => array(
		'ref' => array( 'type' => array( 'string', 'integer' ) ), 'start' => array( 'type' => 'string' ),
		'end' => array( 'type' => array( 'string', 'boolean', 'null' ) ), 'status' => array( 'type' => array( 'string', 'null' ) ),
		'tickets_url' => array( 'type' => array( 'string', 'boolean', 'null' ) ),
		'tickets' => array( 'type' => array( 'object', 'null' ), 'properties' => array( 'total' => array( 'type' => array( 'integer', 'null' ) ), 'available' => array( 'type' => array( 'integer', 'null' ) ), 'url' => array( 'type' => 'string' ) ) ),
		'prices' => array( 'type' => 'array', 'items' => array( 'type' => 'object' ) ),
		'venue' => array( 'type' => array( 'object', 'boolean', 'null' ) ), 'custom' => array( 'type' => 'object' ),
		'production' => array( 'type' => 'object', 'properties' => array(
			'title' => array( 'type' => 'string' ), 'categories' => array( 'type' => 'array', 'items' => array( 'type' => 'string' ) ),
			'description' => array( 'type' => array( 'string', 'boolean', 'null' ) ), 'img' => array( 'type' => array( 'string', 'boolean', 'null' ) ),
		), 'required' => array( 'title' ) ),
		'subscription_id' => $string, 'created_at' => array( 'type' => 'string' ), 'updated_at' => array( 'type' => 'string' ),
	), 'required' => array( 'ref', 'start', 'production', 'subscription_id' ) );
	$list = function( $field, $items ) use ( $integer ) {
		return array( 'type' => 'object', 'properties' => array( 'count' => $integer, $field => array( 'type' => 'array', 'items' => $items ) ), 'required' => array( 'count', $field ) );
	};
	$outputs = array(
		'list-subscriptions' => $list( 'subscriptions', $summary ),
		'get-subscription' => $summary,
		'create-subscription' => $summary,
		'config-subscription' => array( 'type' => 'object', 'properties' => array( 'subscription_id' => $string, 'url' => array( 'type' => 'string', 'format' => 'uri' ), 'instructions' => array( 'type' => 'string' ) ), 'required' => array( 'subscription_id', 'url', 'instructions' ) ),
		'set-default-subscription' => $summary,
		'activate-subscription' => $summary,
		'deactivate-subscription' => $summary,
		'get-events' => $list( 'events', $event ),
		'get-event' => $event,
		'list-event-categories' => $list( 'categories', array( 'type' => 'object', 'properties' => array( 'name' => array( 'type' => 'string' ), 'events' => $integer ), 'required' => array( 'name', 'events' ) ) ),
		'get-logs' => $list( 'logs', array( 'type' => 'object', 'properties' => array( 'subscription_id' => $string, 'message' => array( 'type' => 'string' ), 'updated_at' => array( 'type' => 'string' ) ), 'required' => array( 'subscription_id', 'message', 'updated_at' ) ) ),
	);
	$outputs['get-subscription']['properties'] += array( 'configuration_complete' => $boolean, 'connection_errors' => $integer, 'configured_fields' => array( 'type' => 'array', 'items' => array( 'type' => 'string' ) ) );
	$outputs['create-subscription']['properties']['configuration_url'] = array( 'type' => 'string', 'format' => 'uri' );
	$outputs['get-events']['properties'] += array( 'total' => $integer, 'page' => $integer, 'limit' => $integer, 'totalPages' => $integer, 'hasNextPage' => $boolean, 'hasPreviousPage' => $boolean, 'refreshed' => $boolean, 'sync' => array( 'type' => 'array', 'items' => array( 'type' => 'object', 'properties' => array( 'subscription_id' => $string, 'refreshed' => $boolean ), 'required' => array( 'subscription_id', 'refreshed' ) ) ) );
	$definitions = array(
		'list-subscriptions' => array( 'List subscriptions', 'List locally configured Jeero subscriptions. Does not return credentials.', $theater + array( 'active_only' => array( 'type' => 'boolean' ) ), array(), true ),
		'get-subscription' => array( 'Get subscription', 'Check a subscription and its configuration completeness with Jeero. Credentials and raw diagnostics stay private.', $subscription, array(), false ),
		'create-subscription' => array( 'Create subscription', 'Create a Jeero subscription for this WordPress site. Only settings.theater may be supplied. Use config-subscription to enter credentials privately in WordPress.', $settings + array( 'subscription_key' => $string, 'label' => $string, 'is_default' => array( 'type' => 'boolean' ) ), array( 'subscription_key', 'label' ), false ),
		'config-subscription' => array( 'Configure subscription', 'Return the private WordPress settings URL. The user must open it themselves; never inspect the page or enter credentials in chat. Optional settings.theater selects the ticketing system.', $subscription + $settings, array(), false ),
		'set-default-subscription' => array( 'Set default subscription', 'Choose the subscription used when a single-subscription operation omits its selector.', $subscription, array( 'subscription' ), false ),
		'activate-subscription' => array( 'Activate subscription', 'Activate this site\'s subscription in Jeero/Mother, resuming its remote delivery.', $subscription, array( 'subscription' ), false ),
		'deactivate-subscription' => array( 'Deactivate subscription', 'Deactivate this site\'s subscription in Jeero/Mother, pausing remote delivery without deleting imported data.', $subscription, array( 'subscription' ), false ),
		'get-events' => array( 'Get events', 'Return paginated Jeero data from imported calendar posts, refreshing active subscriptions at most once per minute. Includes all subscriptions unless filtered. Source metadata is collected on import; duplicate calendar copies are returned once.', $filters + $limit + array( 'status' => $string, 'category' => $string, 'page' => array( 'type' => 'integer', 'minimum' => 1, 'maximum' => 1000000 ) ), array(), true ),
		'get-event' => array( 'Get event', 'Return one imported calendar event by ref. Supply subscription if the ref occurs in multiple subscriptions. An explicit subscription is refreshed at most once per minute.', $subscription + array( 'ref' => $string ), array( 'ref' ), true ),
		'list-event-categories' => array( 'List event categories', 'List categories and event counts from imported calendar event metadata. Does not trigger an import.', $filters, array(), true ),
		'get-logs' => array( 'Get logs', 'Return recent imported inbox log messages, optionally filtered by subscription. Keeps the latest 1000 messages; does not return raw connector responses.', $subscription + $limit, array(), true ),
	);
	foreach ( $definitions as $name => $definition ) {
		list( $label, $description, $properties, $required, $readonly ) = $definition;
		wp_register_ability( 'jeero/' . $name, array(
			'label' => $label,
			'description' => $description,
			'category' => 'jeero',
			'input_schema' => array(
				'type' => 'object', 'properties' => $properties,
				'required' => $required, 'additionalProperties' => false, 'default' => array(),
			),
			'output_schema' => $outputs[ $name ],
			'permission_callback' => function() use ( $name ) { return can_execute( $name ); },
			'execute_callback' => function( $input ) use ( $name ) { return execute( $name, $input ); },
			'meta' => array(
				'show_in_rest' => true,
				'mcp' => array( 'public' => true ),
				'annotations' => array( 'readonly' => $readonly, 'destructive' => false, 'idempotent' => 'create-subscription' !== $name ),
			),
		) );
	}
}

/**
 * Creates a public error without exposing connector diagnostics or credentials.
 *
 * @since	1.?
 *
 * @param	string	$code   	The error code.
 * @param	string	$message	The public error message.
 * @return	\WP_Error	The public ability error.
 */
function error( $code = 'jeero_request_failed', $message = 'Jeero could not complete this request. Check the subscription in WordPress and try again.' ) {
	return new \WP_Error( $code, $message );
}

/**
 * Resolves a subscription ID, local key or default subscription.
 *
 * @since	1.?
 *
 * @param	string|null	$selector	The subscription ID or local key. Defaults to null.
 * @return	string|\WP_Error	The subscription ID, or an error if no unique subscription is available.
 */
function resolve_subscription( $selector = null ) {
	$subscriptions = Db\get_subscriptions();
	if ( null !== $selector ) {
		if ( isset( $subscriptions[ $selector ] ) ) {
			return $selector;
		}
		foreach ( $subscriptions as $id => $data ) {
			if ( ( $data['subscription_key'] ?? null ) === $selector ) {
				return (string) $id;
			}
		}
		return error( 'jeero_unknown_subscription', 'Unknown Jeero subscription.' );
	}
	$default = get_option( 'jeero/abilities/default_subscription' );
	if ( $default && isset( $subscriptions[ $default ] ) && empty( $subscriptions[ $default ]['inactive'] ) ) {
		return $default;
	}
	$active = array_filter( $subscriptions, function( $item ) { return empty( $item['inactive'] ); } );
	if ( 1 === count( $active ) ) {
		return (string) key( $active );
	}
	return error( 'jeero_subscription_required', 'Select a Jeero subscription explicitly or set a default subscription.' );
}

/**
 * Gets the public summary of a locally configured subscription.
 *
 * @since	1.?
 *
 * @param	string	$id	The subscription ID.
 * @return	array	The subscription summary without credentials.
 */
function subscription_summary( $id ) {
	$data = Db\get_subscription( $id );
	$settings = $data['settings'] ?? array();
	return array(
		'subscription_id' => $id,
		'subscription_key' => $data['subscription_key'] ?? $id,
		'label' => $data['label'] ?? $id,
		'theater' => (string) ( $settings['theater'] ?? ( $data['theater']['name'] ?? '' ) ),
		'is_default' => get_option( 'jeero/abilities/default_subscription' ) === $id,
		'is_active' => empty( $data['inactive'] ),
	);
}

/**
 * Lists public subscription summaries matching the supplied filters.
 *
 * @since	1.?
 *
 * @param	array	$input	The ability input or event filters.
 * @return	array	The number of matching subscriptions and their summaries.
 */
function list_subscriptions( $input ) {
	$items = array();
	foreach ( Db\get_subscriptions() as $id => $data ) {
		$summary = subscription_summary( (string) $id );
		if ( isset( $input['theater'] ) && $summary['theater'] !== $input['theater'] ) {
			continue;
		}
		if ( ! empty( $input['active_only'] ) && ! $summary['is_active'] ) {
			continue;
		}
		$items[] = $summary;
	}
	return array( 'count' => count( $items ), 'subscriptions' => $items );
}

/**
 * Checks a subscription and reports configuration completeness without credentials.
 *
 * @since	1.?
 *
 * @param	string	$id	The subscription ID.
 * @return	array|\WP_Error	The public subscription details, or a connector error.
 */
function get_subscription( $id ) {
	$subscription = Subscriptions\get_subscription( $id );
	if ( is_wp_error( $subscription ) ) {
		return error();
	}
	$summary = subscription_summary( $id );
	$settings = $subscription->get_settings();
	$summary['configured_fields'] = array_keys( array_filter( $settings, function( $value ) { return null !== $value && '' !== $value && false !== $value && array() !== $value; } ) );
	$summary['configuration_complete'] = '' !== $summary['theater'];
	$summary['connection_errors'] = 0;
	foreach ( $subscription->fields as $field ) {
		if ( ! empty( $field['required'] ) && ( ! isset( $settings[ $field['name'] ] ) || '' === $settings[ $field['name'] ] || false === $settings[ $field['name'] ] || array() === $settings[ $field['name'] ] ) ) {
			$summary['configuration_complete'] = false;
		}
		if ( 'error' === ( $field['type'] ?? '' ) ) {
			$summary['connection_errors']++;
		}
	}
	return $summary;
}

/**
 * Updates a subscription's ticketing system without persisting runtime credentials.
 *
 * @since	1.?
 *
 * @param	string	$id      	The subscription ID.
 * @param	array 	$settings	The validated ticketing system settings.
 * @return	true|\WP_Error	True on success, or a public configuration error.
 */
function select_theater( $id, $settings ) {
	$subscription = new Subscriptions\Subscription( $id );
	$merged = array_merge( $subscription->get_settings(), $settings );
	$response = Mother\update_subscription( $id, $merged );
	if ( is_wp_error( $response ) || ! is_array( $response ) ) {
		return error();
	}
	// Runtime settings filters may inject credentials that must not be persisted.
	Db\save_subscription( $id, array_merge( Db\get_subscription( $id )['settings'], $settings ) );
	return true;
}

/**
 * Creates a subscription with a local key, label and optional ticketing system.
 *
 * @since	1.?
 *
 * @param	array	$input	The ability input or event filters.
 * @return	array|\WP_Error	The public subscription summary and configuration URL, or an error.
 */
function create_subscription( $input ) {
	$existing = resolve_subscription( $input['subscription_key'] );
	if ( ! is_wp_error( $existing ) ) {
		return error( 'jeero_duplicate_subscription', 'This subscription key already exists.' );
	}
	$id = Subscriptions\add_subscription();
	if ( is_wp_error( $id ) || ! is_string( $id ) || '' === $id ) {
		return error();
	}
	if ( ! is_wp_error( resolve_subscription( $id ) ) ) {
		return error( 'jeero_duplicate_subscription', 'Jeero returned an existing subscription ID.' );
	}
	// Persist the remote ID first so a failed configuration never loses the new subscription.
	Db\save_subscription( $id, array() );
	$subscriptions = Db\get_subscriptions();
	$subscriptions[ $id ]['subscription_key'] = $input['subscription_key'];
	$subscriptions[ $id ]['label'] = $input['label'];
	update_option( Db\JEERO_OPTION_SUBSCRIPTION, $subscriptions, false );
	if ( ( $input['is_default'] ?? ! get_option( 'jeero/abilities/default_subscription' ) ) ) {
		update_option( 'jeero/abilities/default_subscription', $id, false );
	}
	if ( ! empty( $input['settings'] ) ) {
		$configured = select_theater( $id, $input['settings'] );
		if ( is_wp_error( $configured ) ) {
			return $configured;
		}
	}
	return subscription_summary( $id ) + array( 'configuration_url' => \Jeero\Admin\Subscriptions\get_admin_edit_url( $id ) );
}

/**
 * Refreshes selected active subscriptions while sharing the inbox lock with cron.
 *
 * @since	1.?
 *
 * @param	array	$input	The ability input or event filters.
 * @return	array|\WP_Error	The refresh results, or an error if synchronization fails.
 */
function sync( $input ) {
	$targets = list_subscriptions( $input + array( 'active_only' => true ) )['subscriptions'];
	if ( isset( $input['subscription_id'] ) ) {
		$targets = array_filter( $targets, function( $item ) use ( $input ) { return $item['subscription_id'] === $input['subscription_id']; } );
	}
	$settings = array();
	$results = array();
	foreach ( $targets as $target ) {
		$id = $target['subscription_id'];
		$last = (int) get_transient( 'jeero_sync_' . md5( $id ) );
		$refresh = time() - $last >= MINUTE_IN_SECONDS;
		$results[] = array( 'subscription_id' => $id, 'refreshed' => $refresh );
		if ( $refresh ) {
			$subscription = new Subscriptions\Subscription( $id );
			$settings[ $id ] = $subscription->get_settings();
		}
	}
	if ( $settings ) {
		$result = Inbox\pickup_items( $settings );
		if ( is_wp_error( $result ) ) {
			return 'jeero_sync_running' === $result->get_error_code() ? $result : error();
		}
		if ( ! empty( $result['failed'] ) ) {
			return error( 'jeero_import_failed', 'Some inbox items failed to import and remain pending. Check the Jeero logs in WordPress.' );
		}
		foreach ( array_keys( $settings ) as $id ) {
			set_transient( 'jeero_sync_' . md5( $id ), time(), MINUTE_IN_SECONDS );
		}
	}
	return array( 'count' => count( $results ), 'subscriptions' => array_values( $results ) );
}

/**
 * Executes a Jeero ability with permission checks and redacted results.
 *
 * @since	1.?
 *
 * @param	string	$operation	The ability name without the Jeero namespace.
 * @param	array 	$input    	The ability input or event filters.
 * @return	array|\WP_Error	The public ability result, or an execution error.
 */
function execute( $operation, $input ) {
	if ( ! function_exists( 'Jeero\\Db\\Subscriptions\\get_subscriptions' ) ) {
		return error( 'jeero_not_ready', 'Jeero has not finished loading.' );
	}
	// Also enforce permissions for PHP callers that invoke this dispatcher directly.
	if ( ! can_execute( $operation ) ) {
		return error( 'jeero_forbidden', 'You do not have permission to execute this Jeero ability.' );
	}
	try {
		$result = run( $operation, $input );
		return is_wp_error( $result ) ? $result : Data\clean( $result );
	} catch ( \Throwable $e ) {
		// Connector errors can contain submitted credentials; return a fixed message.
		return error();
	}
}

/**
 * Dispatches a validated Jeero ability to its subscription or event operation.
 *
 * @since	1.?
 *
 * @param	string	$operation	The ability name without the Jeero namespace.
 * @param	array 	$input    	The ability input or event filters.
 * @return	array|\WP_Error	The operation result, or an error.
 */
function run( $operation, $input ) {
	if ( 'list-subscriptions' === $operation ) {
		return list_subscriptions( $input );
	}
	if ( 'create-subscription' === $operation ) {
		return create_subscription( $input );
	}
	$id = null;
	if ( isset( $input['subscription'] ) || in_array( $operation, array( 'get-subscription', 'config-subscription' ), true ) ) {
		$id = resolve_subscription( $input['subscription'] ?? null );
		if ( is_wp_error( $id ) ) {
			return $id;
		}
		$input['subscription_id'] = $id;
	}
	switch ( $operation ) {
		case 'get-subscription':
			return get_subscription( $id );
		case 'config-subscription':
			if ( ! empty( $input['settings'] ) ) {
				$configured = select_theater( $id, $input['settings'] );
				if ( is_wp_error( $configured ) ) {
					return $configured;
				}
			}
			return array( 'subscription_id' => $id, 'url' => \Jeero\Admin\Subscriptions\get_admin_edit_url( $id ), 'instructions' => 'Open this WordPress admin page yourself and sign in. Enter credentials only there. Do not let the agent inspect the page. Then check jeero/get-subscription.' );
		case 'set-default-subscription':
			if ( ! empty( Db\get_subscription( $id )['inactive'] ) ) {
				return error( 'jeero_inactive_subscription', 'Activate the subscription before making it the default.' );
			}
			update_option( 'jeero/abilities/default_subscription', $id, false );
			return subscription_summary( $id );
		case 'activate-subscription':
		case 'deactivate-subscription':
			$subscription = new Subscriptions\Subscription( $id );
			$method = 'activate-subscription' === $operation ? 'activate' : 'deactivate';
			$result = $subscription->$method();
			if ( is_wp_error( $result ) || true !== $result ) {
				return error();
			}
			if ( 'deactivate' === $method && get_option( 'jeero/abilities/default_subscription' ) === $id ) {
				delete_option( 'jeero/abilities/default_subscription' );
			}
			return subscription_summary( $id );
		case 'get-events':
		case 'get-event':
			$valid = Data\validate_filters( $input );
			if ( is_wp_error( $valid ) ) {
				return $valid;
			}
			$sync = null;
			if ( 'get-events' === $operation || null !== $id ) {
				$sync = sync( $input );
				if ( is_wp_error( $sync ) ) {
					return $sync;
				}
			}
			if ( 'get-event' === $operation ) {
				return Data\get_event( $input );
			}
			$result = Data\get_events( $input );
			if ( is_wp_error( $result ) ) {
				return $result;
			}
			foreach ( $result['events'] as &$event ) {
				$event = array_intersect_key( $event, array_flip( array( 'subscription_id', 'ref', 'start', 'end', 'status', 'tickets', 'production' ) ) );
				$event['production'] = array_intersect_key( $event['production'], array_flip( array( 'title', 'categories' ) ) );
			}
			unset( $event );
			return $result + array( 'sync' => $sync['subscriptions'], 'refreshed' => in_array( true, array_column( $sync['subscriptions'], 'refreshed' ), true ) );
		case 'list-event-categories':
			return Data\get_categories( $input );
		case 'get-logs':
			$logs = array_reverse( get_option( 'jeero/abilities/logs', array() ) );
			if ( null !== $id ) {
				$logs = array_values( array_filter( $logs, function( $log ) use ( $id ) { return $log['subscription_id'] === $id; } ) );
			}
			$logs = array_slice( $logs, 0, $input['limit'] ?? 20 );
			return array( 'count' => count( $logs ), 'logs' => $logs );
	}
	return error( 'jeero_unknown_ability', 'Unknown Jeero ability.' );
}
