<?php
/**
 * @group ticket-context
 */

use Jeero\Subscriptions\Subscription;

class Jeero_Test_Ticket_Context_Calendar extends \Jeero\Calendars\Post_Based_Calendar {
}

class Ticket_Context_Test extends Jeero_Test {

	function test_canonical_ticket_context_meta_is_written_for_post_based_imports() {

		$post_id = wp_insert_post(
			array(
				'post_title'  => 'Event',
				'post_status' => 'publish',
				'post_type'   => 'post',
			)
		);

		$subscription = new Subscription( 'a fake ID' );
		$calendar     = new Jeero_Test_Ticket_Context_Calendar();

		$calendar->update_ticket_context_meta(
			$post_id,
			array(
				'tickets_url' => 'https://tickets.example.com/show/123',
				'status'      => 'soldout',
			),
			$subscription
		);

		$this->assertEquals( 'https://tickets.example.com/show/123', get_post_meta( $post_id, 'jeero/import/post/tickets_url', true ) );
		$this->assertEquals( 'soldout', get_post_meta( $post_id, 'jeero/import/post/tickets_status', true ) );
		$this->assertEquals( 'a fake ID', get_post_meta( $post_id, 'jeero/import/post/subscription', true ) );

	}
}
