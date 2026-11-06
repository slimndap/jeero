<?php
class Jeero_Test_Tapos extends WP_UnitTestCase {

	function test_widget_uses_canonical_event_and_keeps_sessions_out_of_html() {
		\Jeero\Db\Subscriptions\save_subscription( 'tapos-test', array( 'theater' => 'tapos', 'baseurl' => 'https://tickets.example/websales/sales/master/book' ) );
		$post_id = self::factory()->post->create();
		update_post_meta( $post_id, 'jeero/import/post/tickets_url', 'https://original.example/book?perfcode=21907' );
		update_post_meta( $post_id, 'jeero/import/post/subscription', 'tapos-test' );
		$args = array( 'event_id' => $post_id, 'tickets_url' => 'https://evil.example/book?perfcode=999' );
		$html = jeero_get_theater_widget( 'tickets_inline', 'tapos-test', $args );
		$this->assertStringContainsString( 'data-jeero-tapos-url="https://tickets.example/websales/sales/master/book?perfcode=21907', $html );
		$this->assertStringContainsString( 'https://original.example/book?perfcode=21907', $html );
		$this->assertStringNotContainsString( 'tapos_id', $html );
		$this->assertStringNotContainsString( 'evil.example', $html );
		$this->assertStringNotContainsString( 'src=', $html );
		$this->assertTrue( wp_script_is( 'jeero/theaters/tapos', 'enqueued' ) );

		update_post_meta( $post_id, 'jeero/import/post/widgets/tickets_inline/url', 'https://old.example/book?perfcode=21907&tapos_id=shared' );
		$this->assertSame( $html, jeero_get_theater_widget( 'tickets_inline', 'tapos-test', $args ) );
		update_post_meta( $post_id, 'jeero/import/post/subscription', 'different-subscription' );
		$this->assertSame( '', jeero_get_theater_widget( 'tickets_inline', 'tapos-test', $args ) );
	}

	function test_missing_configuration_keeps_direct_booking_and_button_routes_to_selected_page() {
		$page_id = self::factory()->post->create( array( 'post_type' => 'page' ) );
		\Jeero\Db\Subscriptions\save_subscription( 'tapos-test', array( 'theater' => 'tapos', 'widgets/tickets_button/tickets_page' => $page_id ) );
		$post_id = self::factory()->post->create();
		update_post_meta( $post_id, 'jeero/import/post/tickets_url', 'https://original.example/book?perfcode=21907' );
		update_post_meta( $post_id, 'jeero/import/post/subscription', 'tapos-test' );
		$args = array( 'event_id' => $post_id );
		$this->assertStringNotContainsString( '<iframe', jeero_get_theater_widget( 'tickets_inline', 'tapos-test', $args ) );
		$this->assertStringContainsString( 'jeero_event=' . $post_id, jeero_get_theater_widget( 'tickets_button', 'tapos-test', $args ) );
		update_post_meta( $post_id, 'jeero/import/post/tickets_status', 'soldout' );
		$this->assertSame( '', jeero_get_theater_widget( 'tickets_inline', 'tapos-test', $args ) );
		$this->assertStringNotContainsString( '<a ', jeero_get_theater_widget( 'tickets_button', 'tapos-test', $args ) );
	}
}
