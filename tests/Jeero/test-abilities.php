<?php
use Jeero\Abilities\Data;
use Jeero\Db\Subscriptions as Db;
use Jeero\Inbox;

class Abilities_Test_Calendar extends \Jeero\Calendars\Post_Based_Calendar {
	function __construct( $slug = 'Abilities_Test_Calendar' ) {
		$this->slug = $slug;
		$this->post_type = 'post';
	}

	function process_data( $result, $data, $raw, $theater, $subscription ) {
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		$name = 'jeero-fixture-' . md5( $this->slug . $subscription->ID . $data['ref'] );
		$posts = get_posts( array( 'name' => $name, 'post_type' => 'post', 'post_status' => 'any' ) );
		$post_id = wp_insert_post( array(
			'ID' => $posts ? $posts[0]->ID : 0,
			'post_name' => $name, 'post_type' => 'post', 'post_status' => 'publish',
			'post_title' => $data['production']['title'],
			'post_content' => wp_slash( $data['production']['description'] ?? '' ),
		), true );
		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}
		$updated = $this->update_ticket_context_meta( $post_id, $data, $subscription );
		return is_wp_error( $updated ) ? $updated : $post_id;
	}
}

class Abilities_Test extends Jeero_Test {
	private $requests = array();
	private $inbox = array();
	private $remote_error = false;
	private $http;
	private $post;
	private $get;
	private $calendars_before;

	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		if ( ! function_exists( 'wp_get_ability' ) ) {
			self::markTestSkipped( 'The Abilities API requires WordPress 6.9 or newer.' );
		}
	}

	protected function setUp(): void {
		parent::setUp();
		global $_jeero_calendars;
		$this->calendars_before = $_jeero_calendars;
		$this->calendar = new Abilities_Test_Calendar();
		$_jeero_calendars[] = $this->calendar;
		add_filter( 'jeero/inbox/process/item/import/calendar=' . $this->calendar->slug, array( $this->calendar, 'import' ), 10, 5 );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		update_option( Db\JEERO_OPTION_SUBSCRIPTION, array(
			'one' => array( 'settings' => array( 'theater' => 'stager', 'api_key' => 'private-key-123456', 'calendar' => array( $this->calendar->slug ) ), 'subscription_key' => 'main', 'label' => 'Main', 'inactive' => false ),
			'two' => array( 'settings' => array( 'theater' => 'veezi', 'calendar' => array( $this->calendar->slug ) ), 'inactive' => false ),
		) );
		delete_option( 'jeero/abilities/default_subscription' );
		delete_option( 'jeero/abilities/logs' );
		delete_option( 'jeero/inbox/pickup_lock' );
		delete_transient( 'jeero_sync_' . md5( 'one' ) );
		delete_transient( 'jeero_sync_' . md5( 'two' ) );
		$this->requests = array();
		$this->inbox = array();
		$this->remote_error = false;
		$this->post = function( $response, $endpoint, $args ) {
			$this->requests[] = array( $endpoint, $args );
			if ( $this->remote_error ) {
				return new WP_Error( 'upstream', 'Failed using private-key-123456' );
			}
			if ( 'inbox/big' === $endpoint ) {
				$items = array_values( array_filter( $this->inbox, function( $item ) use ( $args ) { return isset( $args[ $item['subscription_id'] ] ); } ) );
				return $this->response( $items );
			}
			return $this->response( array( 'id' => 'new-id' ) );
		};
		$this->get = function( $response, $endpoint ) {
			$this->requests[] = array( $endpoint );
			if ( $this->remote_error ) {
				return new WP_Error( 'upstream', 'Failed using private-key-123456' );
			}
			return $this->response( array(
				'id' => 'one', 'inactive' => false === strpos( $endpoint, '/deactivate' ) ? false : true,
				'fields' => array( array( 'type' => 'text', 'name' => 'api_key', 'required' => true, 'value' => 'private-key-123456' ) ),
				'theater' => array( 'name' => 'stager', 'title' => 'Stager' ),
			) );
		};
		$this->http = function( $preempt, $args, $url ) {
			if ( 'DELETE' === ( $args['method'] ?? '' ) ) {
				$this->requests[] = array( 'delete', $url );
				return $this->response( array() );
			}
			return new WP_Error( 'test_network_blocked', 'Network access is blocked in abilities tests.' );
		};
		add_filter( 'jeero/mother/post/response', $this->post, 10, 3 );
		add_filter( 'jeero/mother/get/response', $this->get, 10, 2 );
		add_filter( 'pre_http_request', $this->http, 10, 3 );
	}

	protected function tearDown(): void {
		global $_jeero_calendars;
		$_jeero_calendars = $this->calendars_before;
		remove_filter( 'jeero/inbox/process/item/import/calendar=' . $this->calendar->slug, array( $this->calendar, 'import' ), 10 );
		remove_filter( 'jeero/mother/post/response', $this->post, 10 );
		remove_filter( 'jeero/mother/get/response', $this->get, 10 );
		remove_filter( 'pre_http_request', $this->http, 10 );
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	private function response( $body ) {
		return array( 'body' => wp_json_encode( $body ), 'response' => array( 'code' => 200 ) );
	}

	private function call( $name, $input = array() ) {
		$ability = wp_get_ability( 'jeero/' . $name );
		$this->assertNotNull( $ability );
		return $ability->execute( $input );
	}

	private function event( $ref = 'e1', $id = 'one', $start = '2026-11-15 20:00:00' ) {
		return array(
			'ID' => $id . '-' . $ref, 'subscription_id' => $id,
			'action' => 'import', 'item' => 'event', 'theater' => 'one' === $id ? 'stager' : 'veezi',
			'raw' => array( 'api_key' => 'private-key-123456' ),
			'data' => array(
				'ref' => $ref, 'start' => $start, 'status' => 'onsale',
				'production' => array( 'ref' => 'p1', 'title' => 'Test concert', 'description' => '<p>Description</p>', 'categories' => array( 'Music', 'Live' ) ),
				'tickets' => array( 'total' => 200, 'available' => 50 ),
				'custom' => array( 'note' => 'private-key-123456', 'access_token' => 'unknown-token' ),
			),
		);
	}

	function test_all_eleven_abilities_are_registered_without_manual_sync_or_snapshots() {
		$names = array( 'list-subscriptions', 'get-subscription', 'create-subscription', 'config-subscription', 'set-default-subscription', 'activate-subscription', 'deactivate-subscription', 'get-events', 'get-event', 'list-event-categories', 'get-logs' );
		foreach ( $names as $name ) {
			$ability = wp_get_ability( 'jeero/' . $name );
			$this->assertNotNull( $ability );
			$this->assertTrue( $ability->get_meta_item( 'show_in_rest' ) );
			$this->assertTrue( $ability->get_meta_item( 'mcp' )['public'] );
			$this->assertSame( 'jeero', $ability->get_category() );
		}
		$this->assertFalse( wp_has_ability( 'jeero/get-ticket-snapshots' ) );
		foreach ( array( 'sync-subscription', 'sync-subscriptions' ) as $name ) {
			$this->assertFalse( wp_has_ability( 'jeero/' . $name ) );
			$error = \Jeero\Abilities\execute( $name, array() );
			$this->assertWPError( $error );
			$this->assertSame( 'jeero_unknown_ability', $error->get_error_code() );
		}
		$this->assertSame( array(), $this->requests );
	}

	function test_anonymous_users_and_subscribers_cannot_access_abilities() {
		foreach ( array( 0, self::factory()->user->create( array( 'role' => 'subscriber' ) ) ) as $user ) {
			wp_set_current_user( $user );
			foreach ( wp_get_abilities() as $ability ) {
				if ( 0 === strpos( $ability->get_name(), 'jeero/' ) ) {
					$this->assertFalse( $ability->check_permissions( array() ) );
				}
			}
			$this->assertWPError( $this->call( 'get-events' ) );
		}
		$this->assertSame( array(), $this->requests );
	}

	function test_post_editors_can_only_execute_the_three_event_read_abilities() {
		$read_abilities = array( 'get-events', 'get-event', 'list-event-categories' );
		$this->inbox = array( $this->event() );
		foreach ( array( 'contributor', 'author', 'editor' ) as $role ) {
			wp_set_current_user( self::factory()->user->create( array( 'role' => $role ) ) );
			$this->assertFalse( current_user_can( 'manage_options' ) );
			foreach ( wp_get_abilities() as $ability ) {
				if ( 0 === strpos( $ability->get_name(), 'jeero/' ) ) {
					$allowed = in_array( substr( $ability->get_name(), 6 ), $read_abilities, true );
					$this->assertSame( $allowed, $ability->check_permissions( array() ), $ability->get_name() . ' for ' . $role );
				}
			}
			foreach ( $read_abilities as $name ) {
				$this->assertIsArray( $this->call( $name, array( 'subscription' => 'main' ) + ( 'get-event' === $name ? array( 'ref' => 'e1' ) : array() ) ) );
			}
			$this->assertWPError( \Jeero\Abilities\execute( 'get-logs', array() ) );
		}
	}

	function test_input_validation_rejects_credentials_unknown_fields_and_invalid_pagination() {
		foreach ( array(
			array( 'config-subscription', array( 'subscription' => 'one', 'settings' => array( 'api_key' => 'do-not-accept' ) ) ),
			array( 'create-subscription', array( 'subscription_key' => 'third', 'label' => 'Third', 'settings' => array( 'password' => 'do-not-accept' ) ) ),
			array( 'get-events', array( 'limit' => 1001 ) ),
			array( 'get-events', array( 'page' => 0 ) ),
			array( 'get-events', array( 'unknown' => true ) ),
			array( 'get-events', array( 'force' => true ) ),
			array( 'get-event', array() ),
		) as $case ) {
			$this->assertWPError( $this->call( $case[0], $case[1] ) );
		}
		$this->assertSame( array(), $this->requests );
	}

	function test_subscription_summaries_config_link_defaults_and_private_errors() {
		$list = $this->call( 'list-subscriptions', array( 'theater' => 'stager' ) );
		$this->assertSame( 1, $list['count'] );
		$this->assertSame( 'main', $list['subscriptions'][0]['subscription_key'] );
		$this->assertWPError( $this->call( 'get-subscription' ) );
		$this->assertWPError( $this->call( 'get-subscription', array( 'subscription' => 'unknown' ) ) );
		$this->call( 'set-default-subscription', array( 'subscription' => 'main' ) );
		$subscription = $this->call( 'get-subscription' );
		$this->assertTrue( $subscription['configuration_complete'] );
		$this->assertTrue( $subscription['is_default'] );
		$this->assertStringNotContainsString( 'private-key-123456', wp_json_encode( $subscription ) );
		$link = $this->call( 'config-subscription' );
		$this->assertSame( \Jeero\Admin\Subscriptions\get_admin_edit_url( 'one' ), $link['url'] );
		$this->remote_error = true;
		$error = $this->call( 'get-subscription' );
		$this->assertWPError( $error );
		$this->assertStringNotContainsString( 'private-key', $error->get_error_message() );
	}

	function test_create_subscription_keeps_local_alias_and_supports_private_theater_configuration() {
		$result = $this->call( 'create-subscription', array( 'subscription_key' => 'third', 'label' => 'Third', 'settings' => array( 'theater' => 'stager' ) ) );
		$this->assertSame( 'new-id', $result['subscription_id'] );
		$this->assertSame( 'third', $result['subscription_key'] );
		$this->assertSame( 'stager', Db\get_subscription( 'new-id' )['settings']['theater'] );
		$this->assertWPError( $this->call( 'create-subscription', array( 'subscription_key' => 'third', 'label' => 'Duplicate' ) ) );
		$link = $this->call( 'config-subscription', array( 'subscription' => 'third', 'settings' => array( 'theater' => 'veezi' ) ) );
		$this->assertSame( 'new-id', $link['subscription_id'] );
		$this->assertSame( 'veezi', Db\get_subscription( 'new-id' )['settings']['theater'] );
	}

	function test_deactivation_uses_mother_and_preserves_data_and_settings() {
		Inbox\process_item( $this->event() );
		$this->call( 'set-default-subscription', array( 'subscription' => 'one' ) );
		$result = $this->call( 'deactivate-subscription', array( 'subscription' => 'one' ) );
		$this->assertFalse( $result['is_active'] );
		$this->assertFalse( get_option( 'jeero/abilities/default_subscription' ) );
		$this->assertSame( 'private-key-123456', Db\get_subscription( 'one' )['settings']['api_key'] );
		$this->assertSame( 1, Data\get_events( array( 'subscription_id' => 'one' ) )['total'] );
		$this->assertWPError( $this->call( 'set-default-subscription', array( 'subscription' => 'one' ) ) );
		$this->assertTrue( $this->call( 'activate-subscription', array( 'subscription' => 'one' ) )['is_active'] );
		$this->assertContains( array( 'subscriptions/one/deactivate' ), $this->requests );
	}

	function test_event_reads_sync_only_selected_subscriptions_and_throttle_refreshes() {
		$this->inbox = array( $this->event(), $this->event( 'other', 'two' ) );
		$first = $this->call( 'get-events', array( 'subscription' => 'main' ) );
		$this->assertTrue( $first['sync'][0]['refreshed'] );
		$this->assertSame( array( 'one' ), array_keys( $this->requests[0][1] ) );
		$second = $this->call( 'get-events', array( 'subscription' => 'one' ) );
		$this->assertFalse( $second['sync'][0]['refreshed'] );
		$this->assertSame( 1, Data\get_events( array() )['total'] );
		set_transient( 'jeero_sync_' . md5( 'one' ), time() - MINUTE_IN_SECONDS, MINUTE_IN_SECONDS );
		$third = $this->call( 'get-events', array( 'subscription' => 'one' ) );
		$this->assertTrue( $third['sync'][0]['refreshed'] );
		$this->call( 'get-events', array( 'theater' => 'veezi' ) );
		$this->assertSame( 2, Data\get_events( array() )['total'] );
	}

	function test_cron_and_ability_share_lock_and_expired_lock_can_be_recovered() {
		add_option( 'jeero/inbox/pickup_lock', time() + 300, '', false );
		$this->assertWPError( $this->call( 'get-events', array( 'subscription' => 'one' ) ) );
		$this->assertWPError( Inbox\pickup_items() );
		$this->assertSame( array(), $this->requests );
		update_option( 'jeero/inbox/pickup_lock', time() - 10, false );
		$this->assertIsArray( $this->call( 'get-events', array( 'subscription' => 'one' ) ) );
		$this->assertFalse( get_option( 'jeero/inbox/pickup_lock' ) );
	}

	function test_failed_sync_is_retried_and_does_not_return_upstream_credentials() {
		$this->remote_error = true;
		$result = $this->call( 'get-events', array( 'subscription' => 'one' ) );
		$this->assertWPError( $result );
		$this->assertStringNotContainsString( 'private-key', $result->get_error_message() );
		$this->assertFalse( get_transient( 'jeero_sync_' . md5( 'one' ) ) );
		$this->assertFalse( get_option( 'jeero/inbox/pickup_lock' ) );
	}

	function test_expired_importer_cannot_release_its_successors_lock() {
		update_option( 'jeero/inbox/pickup_lock', time() + 600, false );
		Inbox\release_pickup_lock( time() - 300 );
		$this->assertGreaterThan( time(), get_option( 'jeero/inbox/pickup_lock' ) );
	}

	function test_event_postmeta_filters_paginates_updates_and_disambiguates_subscriptions() {
		Inbox\process_item( $this->event() );
		Inbox\process_item( $this->event( 'e2', 'one', '2026-11-16T20:00:00+01:00' ) );
		Inbox\process_item( $this->event( 'e1', 'two' ) );
		$result = $this->call( 'get-events', array( 'subscription' => 'main', 'category' => 'Music', 'from' => '2026-11-15', 'to' => '2026-11-16', 'query' => 'concert', 'limit' => 1, 'page' => 2 ) );
		$this->assertSame( 2, $result['total'] );
		$this->assertSame( 'e2', $result['events'][0]['ref'] );
		$this->assertTrue( $result['hasPreviousPage'] );
		$this->assertFalse( $result['hasNextPage'] );
		$this->assertSame( 0, $this->call( 'get-events', array( 'category' => 'Mus' ) )['total'] );
		$this->assertWPError( $this->call( 'get-event', array( 'ref' => 'e1' ) ) );
		$event = $this->call( 'get-event', array( 'ref' => 'e1', 'subscription' => 'one' ) );
		$this->assertSame( 200, $event['tickets']['total'] );
		$this->assertSame( '[REDACTED]', $event['custom']['note'] );
		$this->assertSame( '[REDACTED]', $event['custom']['access_token'] );
		$this->assertArrayNotHasKey( 'raw', $event );
		$posts = get_posts( array( 'meta_key' => '_jeero_event_calendar', 'meta_value' => $this->calendar->slug ) );
		foreach ( $posts as $post ) {
			$stored = wp_json_encode( get_post_meta( $post->ID, '_jeero_event_data', true ) );
			$this->assertStringNotContainsString( 'private-key-123456', $stored );
			$this->assertStringNotContainsString( 'unknown-token', $stored );
		}
		$changed = $this->event();
		$changed['data']['status'] = 'soldout';
		Inbox\process_item( $changed );
		$this->assertSame( 3, Data\get_events( array() )['total'] );
		$this->assertSame( 1, $this->call( 'get-events', array( 'status' => 'soldout' ) )['total'] );
		$categories = $this->call( 'list-event-categories', array( 'subscription' => 'main', 'date' => '2026-11-15' ) );
		$this->assertSame( array( array( 'name' => 'Live', 'events' => 1 ), array( 'name' => 'Music', 'events' => 1 ) ), $categories['categories'] );
	}

	function test_calendar_read_methods_use_existing_posts_and_preserve_escaped_source_data() {
		$item = $this->event();
		$item['data']['production']['description'] = 'A "quoted" title and C:\\Shows\\Today';
		$post_id = Inbox\process_item( $item );
		$this->assertSame( 'post', get_post_type( $post_id ) );
		$event = $this->calendar->get_event( 'e1', array( 'subscription_id' => 'one' ) );
		$this->assertSame( $item['data']['production']['description'], $event['production']['description'] );
		$this->assertSame( 1, count( $this->calendar->get_events() ) );
		$this->assertSame( 2, $this->calendar->list_event_categories()['count'] );
		wp_trash_post( $post_id );
		$this->assertSame( 0, Data\get_events( array() )['total'] );
		wp_untrash_post( $post_id );
		$this->assertSame( 1, Data\get_events( array() )['total'] );
		wp_delete_post( $post_id, true );
		$this->assertNull( $this->calendar->get_event( 'e1' ) );
	}

	function test_event_reads_deduplicate_multiple_calendar_imports_without_an_event_table() {
		global $_jeero_calendars;
		$second = new Abilities_Test_Calendar( 'Second_Test_Calendar' );
		$_jeero_calendars[] = $second;
		add_filter( 'jeero/inbox/process/item/import/calendar=' . $second->slug, array( $second, 'import' ), 10, 5 );
		$settings = Db\get_subscription( 'one' )['settings'];
		$settings['calendar'][] = $second->slug;
		Db\save_subscription( 'one', $settings );
		$reject_table = function( $sql ) {
			$this->assertStringNotContainsString( 'jeero_events', $sql );
			return $sql;
		};
		add_filter( 'query', $reject_table );
		try {
			Inbox\process_item( $this->event() );
			$this->assertSame( 1, count( $this->calendar->get_events() ) );
			$this->assertSame( 1, count( $second->get_events() ) );
			$this->assertSame( 1, Data\get_events( array() )['total'] );
			$this->assertSame( 'e1', Data\get_event( array( 'ref' => 'e1' ) )['ref'] );
			$this->assertSame( array( array( 'name' => 'Live', 'events' => 1 ), array( 'name' => 'Music', 'events' => 1 ) ), Data\get_categories( array() )['categories'] );
			$copy = get_posts( array( 'meta_key' => '_jeero_event_calendar', 'meta_value' => $second->slug ) )[0];
			$latest = get_post_meta( $copy->ID, '_jeero_event_data', true );
			$latest['status'] = 'soldout';
			$latest['updated_at'] = gmdate( 'Y-m-d H:i:s', time() + 60 );
			update_post_meta( $copy->ID, '_jeero_event_data', wp_slash( $latest ) );
			$this->assertSame( 0, Data\get_events( array( 'status' => 'onsale' ) )['total'] );
			$this->assertSame( 1, Data\get_events( array( 'status' => 'soldout' ) )['total'] );
		} finally {
			remove_filter( 'query', $reject_table );
			remove_filter( 'jeero/inbox/process/item/import/calendar=' . $second->slug, array( $second, 'import' ), 10 );
		}
	}

	function test_scoped_sync_never_consumes_another_subscriptions_inbox_items() {
		remove_filter( 'jeero/mother/post/response', $this->post, 10 );
		$unscoped = function() { return $this->response( array( $this->event(), $this->event( 'other', 'two' ) ) ); };
		add_filter( 'jeero/mother/post/response', $unscoped );
		try {
			$this->assertIsArray( $this->call( 'get-events', array( 'subscription' => 'one' ) ) );
		} finally {
			remove_filter( 'jeero/mother/post/response', $unscoped );
		}
		$this->assertSame( 1, Data\get_events( array() )['total'] );
		foreach ( $this->requests as $request ) {
			if ( 'delete' === $request[0] ) {
				parse_str( wp_parse_url( $request[1], PHP_URL_QUERY ), $query );
				$this->assertSame( array( 'one-e1' ), json_decode( $query['inbox_id'], true ) );
			}
		}
	}

	function test_runtime_credentials_are_not_persisted_when_selecting_a_theater() {
		$inject = function( $settings ) { $settings['runtime_token'] = 'runtime-secret'; return $settings; };
		add_filter( 'jeero/subscription/settings', $inject );
		try {
			$this->call( 'config-subscription', array( 'subscription' => 'one', 'settings' => array( 'theater' => 'veezi' ) ) );
			$this->assertSame( '[REDACTED]', Data\clean( 'runtime-secret' ) );
		} finally {
			remove_filter( 'jeero/subscription/settings', $inject );
		}
		$this->assertArrayNotHasKey( 'runtime_token', Db\get_subscription( 'one' )['settings'] );
		$this->assertSame( 'runtime-secret', $this->requests[0][1]['settings']['runtime_token'] );
	}

	function test_event_dates_use_site_timezone_and_invalid_events_remain_pending() {
		update_option( 'timezone_string', 'Europe/Amsterdam' );
		$this->assertIsInt( Inbox\process_item( $this->event( '0', 'one', '2026-11-15T23:30:00Z' ) ) );
		$this->assertSame( 0, Data\get_events( array( 'date' => '2026-11-15' ) )['total'] );
		$this->assertSame( 1, Data\get_events( array( 'date' => '2026-11-16' ) )['total'] );
		$this->assertWPError( Inbox\process_item( $this->event( 'invalid', 'one', '2026-02-30' ) ) );
		$invalid = $this->event( 'bad-categories' );
		$invalid['data']['production']['categories'] = array( array( 'secret' => 'unknown-secret' ) );
		$this->assertWPError( Inbox\process_item( $invalid ) );
		$this->assertSame( 1, Data\get_events( array() )['total'] );
	}

	function test_storage_failure_prevents_acknowledging_the_event() {
		$this->inbox = array( $this->event() );
		$break_insert = function( $check, $post_id, $key ) {
			return '_jeero_event_data' === $key ? false : $check;
		};
		add_filter( 'update_post_metadata', $break_insert, 10, 3 );
		try {
			$this->assertWPError( $this->call( 'get-events', array( 'subscription' => 'one' ) ) );
		} finally {
			remove_filter( 'update_post_metadata', $break_insert, 10 );
		}
		$this->assertSame( 0, Data\get_events( array() )['total'] );
		$this->assertFalse( get_transient( 'jeero_sync_' . md5( 'one' ) ) );
	}

	function test_invalid_dates_are_rejected_before_triggering_a_sync() {
		$this->assertWPError( $this->call( 'get-events', array( 'date' => '2026-02-30' ) ) );
		$this->assertWPError( $this->call( 'get-events', array( 'from' => '2026-12-01', 'to' => '2026-11-01' ) ) );
		$this->assertSame( array(), $this->requests );
	}

	function test_failed_import_is_not_cached_or_acknowledged() {
		$this->inbox = array( $this->event() );
		$fail = function() { return new WP_Error( 'test_failure', 'Import failed.' ); };
		add_filter( 'jeero/inbox/process/item/import/calendar=' . $this->calendar->slug, $fail, 5 );
		try {
			$this->assertWPError( $this->call( 'get-events', array( 'subscription' => 'one' ) ) );
		} finally {
			remove_filter( 'jeero/inbox/process/item/import/calendar=' . $this->calendar->slug, $fail, 5 );
		}
		$this->assertSame( 0, Data\get_events( array() )['total'] );
		foreach ( $this->requests as $request ) {
			if ( 'delete' === $request[0] ) {
				parse_str( wp_parse_url( $request[1], PHP_URL_QUERY ), $query );
				$this->assertSame( array(), json_decode( $query['inbox_id'], true ) );
			}
		}
	}

	function test_inbox_logs_are_filtered_bounded_and_redacted_before_storage() {
		foreach ( array( 'one', 'two' ) as $id ) {
			$item = $this->event( 'log', $id );
			$item['action'] = 'log';
			$item['data'] = array( 'message' => 'Failed with private-key-123456 token=unknown-secret' );
			Inbox\process_item( $item );
		}
		$logs = $this->call( 'get-logs', array( 'subscription' => 'main', 'limit' => 1 ) );
		$this->assertSame( 1, $logs['count'] );
		$this->assertSame( 'one', $logs['logs'][0]['subscription_id'] );
		$this->assertStringNotContainsString( 'private-key', wp_json_encode( get_option( 'jeero/abilities/logs' ) ) );
		$this->assertStringNotContainsString( 'unknown-secret', wp_json_encode( $logs ) );
		update_option( 'jeero/abilities/logs', array_fill( 0, 1000, array( 'message' => 'old' ) ), false );
		Inbox\process_item( $item );
		$this->assertCount( 1000, get_option( 'jeero/abilities/logs' ) );
	}

	function test_redaction_covers_encoded_settings_and_authorization_headers() {
		$value = array( 'message' => 'Bearer other-token private-key-123456 ' . rawurlencode( 'private-key-123456' ) . ' ' . base64_encode( 'private-key-123456' ) );
		$this->assertSame( array( 'message' => 'Bearer [REDACTED] [REDACTED] [REDACTED] [REDACTED]' ), Data\clean( $value ) );
	}

	function test_rest_uses_the_same_validation_and_permissions() {
		$server = rest_get_server();
		$request = new WP_REST_Request( 'GET', '/wp-abilities/v1/abilities/jeero/list-subscriptions/run' );
		$request->set_param( 'input', array( 'theater' => 'stager' ) );
		$response = $server->dispatch( $request );
		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 1, $response->get_data()['count'] );
		$editor = self::factory()->user->create( array( 'role' => 'contributor' ) );
		$subscriber = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		foreach ( array( 'get-events', 'get-event', 'list-event-categories' ) as $name ) {
			wp_set_current_user( $editor );
			$this->requests = array();
			$this->inbox = array( $this->event() );
			$this->inbox[0]['data']['production']['title'] = 'Refreshed via ' . $name;
			delete_transient( 'jeero_sync_' . md5( 'one' ) );
			$events = new WP_REST_Request( 'GET', '/wp-abilities/v1/abilities/jeero/' . $name . '/run' );
			$events->set_param( 'input', array( 'subscription' => 'main' ) + ( 'get-event' === $name ? array( 'ref' => 'e1' ) : array() ) );
			$response = $server->dispatch( $events );
			$this->assertSame( 200, $response->get_status(), $name );
			if ( 'list-event-categories' === $name ) {
				$this->assertSame( 2, $response->get_data()['count'] );
				$this->assertSame( array(), $this->requests );
			} else {
				$event = 'get-event' === $name ? $response->get_data() : $response->get_data()['events'][0];
				$this->assertSame( 'Refreshed via ' . $name, $event['production']['title'] );
				$this->assertSame( 'inbox/big', $this->requests[0][0] );
			}
			$events->set_method( 'POST' );
			$this->assertSame( 405, $server->dispatch( $events )->get_status() );
			$events->set_method( 'GET' );
			wp_set_current_user( $subscriber );
			$this->assertSame( 403, $server->dispatch( $events )->get_status() );
			wp_set_current_user( 0 );
			$this->assertSame( 401, $server->dispatch( $events )->get_status() );
		}
		wp_set_current_user( 0 );
		$this->assertSame( 401, $server->dispatch( $request )->get_status() );
	}
}
