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

		$updated = $calendar->update_ticket_context_meta(
			$post_id,
			array(
				'tickets_url' => 'https://tickets.example.com/show/123',
				'widgets'     => array(
					'tickets_inline' => array(
						'url' => 'https://tickets.example.com/widgets/addtickets?event=123&signature=signed',
					),
				),
				'status'      => 'soldout',
			),
			$subscription
		);

		$this->assertEquals( 'https://tickets.example.com/show/123', get_post_meta( $post_id, 'jeero/import/post/tickets_url', true ) );
		$this->assertEquals( 'https://tickets.example.com/widgets/addtickets?event=123&signature=signed', get_post_meta( $post_id, 'jeero/import/post/widgets/tickets_inline/url', true ) );
		$this->assertEquals( 'soldout', get_post_meta( $post_id, 'jeero/import/post/tickets_status', true ) );
		$this->assertEquals( 'a fake ID', get_post_meta( $post_id, 'jeero/import/post/subscription', true ) );
		$this->assertEquals( 'activetickets', get_post_meta( $post_id, 'jeero/import/post/theater', true ) );
		$this->assertTrue( $updated );
		$this->assertSame( '', get_post_meta( $post_id, '_jeero_event_data', true ) );

	}

	function test_ticketmatic_import_keeps_ticket_link_when_inline_widget_url_cannot_be_built() {

		$post_id      = self::factory()->post->create();
		$subscription = new Subscription( 'a fake ID' );
		$subscription->set( 'settings', array( 'theater' => 'ticketmatic' ) );
		$calendar = new Jeero_Test_Ticket_Context_Calendar();

		update_post_meta( $post_id, 'jeero/import/post/widgets/tickets_inline/url', 'https://tickets.example.org/widgets/addtickets?event=123' );

		$calendar->update_ticket_context_meta(
			$post_id,
			array(
				'tickets_url' => 'https://tickets.example.org/event/123',
			),
			$subscription
		);

		$this->assertSame( '', get_post_meta( $post_id, 'jeero/import/post/widgets/tickets_inline/url', true ) );
		$this->assertSame( 'https://tickets.example.org/event/123', get_post_meta( $post_id, 'jeero/import/post/tickets_url', true ) );

	}

	function test_incomplete_referenced_event_reports_metadata_error() {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			$this->markTestSkipped( 'The Abilities API requires WordPress 6.9 or newer.' );
		}
		$post_id = self::factory()->post->create();
		$calendar = new Jeero_Test_Ticket_Context_Calendar();
		$result = $calendar->update_ticket_context_meta( $post_id, array( 'ref' => 'event-123' ), new Subscription( 'a fake ID' ) );
		$this->assertWPError( $result );
		$this->assertSame( '', get_post_meta( $post_id, '_jeero_event_data', true ) );
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
