<?php
use function Jeero\Campaign_Links\destination;

class Jeero_Test_Campaign_Links extends WP_UnitTestCase {
	function test_new_source_uses_native_reference_and_allows_booking_override() {
		update_option( 'jeero/enable_custom_post_types', true );
		$id = self::factory()->post->create();
		$key = 'jeero/Custom_Post_Type/new_source/ref';
		update_post_meta( $id, $key, 'show-A' );
		$this->assertSame( get_permalink( $id ), destination( 'new_source', 'event', 'show-A' ) );
		$this->assertFalse( destination( 'new_source', 'event', 'show-a' ) );
		$this->assertFalse( destination( 'new_source', 'production', 'show-A' ) );

		$override = function ( $url, $event_id ) {
			return add_query_arg( 'jeero_event', $event_id, home_url( '/tickets/' ) );
		};
		add_filter( 'jeero/campaign_link/url', $override, 10, 2 );
		try {
			$this->assertSame( home_url( '/tickets/?jeero_event=' . $id ), destination( 'new_source', 'event', 'show-A' ) );
		} finally {
			remove_filter( 'jeero/campaign_link/url', $override );
		}

		update_post_meta( self::factory()->post->create(), $key, 'show-A' );
		$this->assertFalse( destination( 'new_source', 'event', 'show-A' ) );
	}
}
