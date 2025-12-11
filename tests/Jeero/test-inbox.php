<?php
/**
 * @group inbox
 */
 
use Jeero\Mother;
use Jeero\Inbox;
use Jeero\Subscriptions;

class Inbox_Test extends Jeero_Test {

	function test_next_pickup_is_scheduled() {

		add_filter( 'jeero/mother/get/response/endpoint=inbox', array( $this, 'get_mock_response_for_get_inbox' ), 10, 3 );

		$now = time();
		
		wp_schedule_single_event( $now, Inbox\PICKUP_ITEMS_HOOK );

		// Get items from inbox to add some subscriptions to the DB.
		$items = Inbox\pickup_items();
		
		$actual = wp_next_scheduled( Inbox\PICKUP_ITEMS_HOOK );
		$expected = $now;
		
		$this->assertGreaterThan( $expected, $actual );

		
	}

	function test_erroring_inbox_item_does_not_block_future_imports() {

		// Always return a predictable inbox item when Mother is queried.
		add_filter( 'jeero/mother/get/response/endpoint=inbox', array( $this, 'get_mock_response_for_get_inbox' ), 10, 3 );

		// Simulate a failure during event processing so the import stops.
		$error_hook = function() {
			throw new \Exception( 'Inbox processing failed.' );
		};
		add_filter( 'jeero/inbox/process/item/import/theater=veezi', $error_hook, 10, 4 );

		try {
			// Execute the import and expect it to blow up immediately.
			Inbox\pickup_items();
			$this->fail( 'Expected an exception while processing the inbox item.' );
		} catch ( \Exception $exception ) {
			$this->assertFalse(
				Inbox\is_item_blocking( array( 'ID' => 'a fake inbox ID' ) ),
				'Inbox items that failed processing should not remain marked as blocking.'
			);
		} finally {
			remove_filter( 'jeero/inbox/process/item/import/theater=veezi', $error_hook, 10 );
		}

	}

	function test_mark_process_item_start_marks_item_as_processing() {

		$item = $this->create_dummy_inbox_item();

		Inbox\mark_process_item_start( $item );

		$this->assertTrue(
			Inbox\is_item_blocking( $item ),
			'An item should appear blocking once processing began and the start marker exists.'
		);

		Inbox\mark_process_item_ended( $item );

	}

	function test_mark_process_item_ended_clears_processing_marker() {

		$item = $this->create_dummy_inbox_item();

		Inbox\mark_process_item_start( $item );
		$this->assertTrue( Inbox\is_item_blocking( $item ) );

		Inbox\mark_process_item_ended( $item );

		$this->assertFalse(
			Inbox\is_item_blocking( $item ),
			'The blocking flag should no longer be present after successful processing.'
		);

	}

	function test_is_item_blocking_returns_false_by_default() {

		$item = $this->create_dummy_inbox_item();

		$this->assertFalse(
			Inbox\is_item_blocking( $item ),
			'New inbox items should not be considered blocking before any processing attempts.'
		);

	}

	function test_process_items_only_removes_successful_results() {

		$deleted_requests = array();
		$http_mock = function( $preempt, $args, $url ) use ( &$deleted_requests ) {
			if ( isset( $args['method'] ) && $args['method'] === 'DELETE' && strpos( $url, '/inbox' ) !== false ) {
				$deleted_requests[] = $url;
				return array(
					'body' => '[]',
					'response' => array(
						'code' => 200,
						'message' => 'OK',
					),
				);
			}

			return $preempt;
		};
		add_filter( 'pre_http_request', $http_mock, 10, 3 );

		$failing_id = 'failing-item';
		$process_filter = function( $result, $data ) use ( $failing_id ) {
			if ( isset( $data['id'] ) && $data['id'] === $failing_id ) {
				return new \WP_Error( 'inbox-failure', 'Simulated processing failure.' );
			}

			return $result;
		};
		add_filter( 'jeero/inbox/process/item', $process_filter, 10, 6 );

		$items = array(
			array_merge(
				$this->create_dummy_inbox_item( 'success-1' ),
				array( 'data' => array( 'id' => 'success-1' ), 'raw' => array() )
			),
			array_merge(
				$this->create_dummy_inbox_item( $failing_id ),
				array( 'data' => array( 'id' => $failing_id ), 'raw' => array() )
			),
			array_merge(
				$this->create_dummy_inbox_item( 'success-2' ),
				array( 'data' => array( 'id' => 'success-2' ), 'raw' => array() )
			),
		);

		Inbox\process_items( $items );

		remove_filter( 'pre_http_request', $http_mock, 10 );
		remove_filter( 'jeero/inbox/process/item', $process_filter, 10 );

		$this->assertCount( 1, $deleted_requests, 'Inbox items should trigger a single removal request.' );

		$query = array();
		$parsed_url = wp_parse_url( $deleted_requests[0] );
		parse_str( $parsed_url['query'], $query );

		$deleted_ids = json_decode( $query['inbox_id'], true );

		$this->assertSame(
			array( 'success-1', 'success-2' ),
			$deleted_ids,
			'Only successfully processed inbox items should be removed.'
		);

	}

	protected function create_dummy_inbox_item( $id = null ) {

		if ( $id === null ) {
			$id = uniqid( 'jeero-inbox-item-', true );
		}

		return array(
			'ID' => $id,
			'action' => 'import',
			'theater' => 'veezi',
			'subscription_id' => 'a fake ID',
		);

	}

}
