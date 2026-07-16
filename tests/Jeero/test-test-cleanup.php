<?php
use Jeero\Test_Cleanup;

class Test_Cleanup_Test extends Jeero_Test {

	protected function tearDown(): void {
		delete_option( Test_Cleanup\OPTION_TEST_SUBSCRIPTIONS );
		parent::tearDown();
	}

	function test_plan_only_contains_posts_for_subscription() {
		$included = self::factory()->post->create();
		$excluded = self::factory()->post->create();
		update_post_meta( $included, Test_Cleanup\META_SUBSCRIPTION, 'test-subscription' );
		update_post_meta( $excluded, Test_Cleanup\META_SUBSCRIPTION, 'another-subscription' );

		$plan = Test_Cleanup\get_plan( 'test-subscription' );

		$this->assertSame( array( $included ), $plan['posts'] );
	}

	function test_cleanup_refuses_unmarked_subscription() {
		$post_id = self::factory()->post->create();
		update_post_meta( $post_id, Test_Cleanup\META_SUBSCRIPTION, 'not-marked' );

		$result = Test_Cleanup\cleanup( 'not-marked' );

		$this->assertWPError( $result );
		$this->assertNotNull( get_post( $post_id ) );
	}

	function test_cleanup_permanently_deletes_marked_posts_and_marker() {
		$post_id = self::factory()->post->create();
		update_post_meta( $post_id, Test_Cleanup\META_SUBSCRIPTION, 'marked' );
		Test_Cleanup\mark( 'marked' );

		$result = Test_Cleanup\cleanup( 'marked' );

		$this->assertSame( array( $post_id ), $result['posts'] );
		$this->assertNull( get_post( $post_id ) );
		$this->assertFalse( Test_Cleanup\is_marked( 'marked' ) );
	}

	function test_cleanup_preserves_shared_thumbnail() {
		$post_id       = self::factory()->post->create();
		$other_post_id = self::factory()->post->create();
		$attachment_id = self::factory()->attachment->create_upload_object( DIR_TESTDATA . '/images/canola.jpg', $post_id );
		set_post_thumbnail( $post_id, $attachment_id );
		set_post_thumbnail( $other_post_id, $attachment_id );
		update_post_meta( $post_id, Test_Cleanup\META_SUBSCRIPTION, 'shared-image' );
		Test_Cleanup\mark( 'shared-image' );

		$plan   = Test_Cleanup\get_plan( 'shared-image' );
		$result = Test_Cleanup\cleanup( 'shared-image' );

		$this->assertSame( array(), $plan['attachments'] );
		$this->assertSame( array(), $result['attachments'] );
		$this->assertNotNull( get_post( $attachment_id ) );
	}
}
