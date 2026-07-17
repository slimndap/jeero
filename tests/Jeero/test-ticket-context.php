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
		$subscription->set(
			'theater',
			array(
				'name' => 'activetickets',
			)
		);
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
		$this->assertEquals( 'activetickets', get_post_meta( $post_id, 'jeero/import/post/theater', true ) );

	}

	function test_canonical_ticket_context_uses_subscription_theater_setting_as_fallback() {

		$post_id      = self::factory()->post->create();
		$subscription = new Subscription( 'a fake ID' );
		$subscription->set(
			'settings',
			array(
				'theater' => 'ActiveTickets',
			)
		);

		$calendar = new Jeero_Test_Ticket_Context_Calendar();
		$calendar->update_ticket_context_meta( $post_id, array(), $subscription );

		$this->assertEquals( 'activetickets', get_post_meta( $post_id, 'jeero/import/post/theater', true ) );

	}

	function test_footprint_message_includes_import_theater_label() {

		$post_id = self::factory()->post->create();
		update_post_meta( $post_id, 'jeero/import/post/theater', 'activetickets' );

		$message = \Jeero\Footprint\get_singular_footprint_message( $post_id, time() );

		$this->assertStringContainsString( 'imported by Jeero from ActiveTickets', $message );

	}

	function test_footprint_message_remains_generic_without_import_theater() {

		$post_id = self::factory()->post->create();
		$message = \Jeero\Footprint\get_singular_footprint_message( $post_id, time() );

		$this->assertStringContainsString( 'imported by Jeero on', $message );
		$this->assertStringNotContainsString( 'imported by Jeero from', $message );

	}
}
