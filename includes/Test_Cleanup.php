<?php
/**
 * Safe cleanup helpers for disposable guide-test imports.
 */
namespace Jeero\Test_Cleanup;

const OPTION_TEST_SUBSCRIPTIONS = 'jeero_test_subscriptions';
const META_SUBSCRIPTION         = 'jeero/import/post/subscription';

/**
 * Mark a subscription as created for an automated guide test.
 *
 * @param string $subscription_id Subscription ID.
 * @return void
 */
function mark( $subscription_id ) {
	$subscription_id = sanitize_text_field( (string) $subscription_id );
	$marked           = get_option( OPTION_TEST_SUBSCRIPTIONS, array() );

	$marked[ $subscription_id ] = time();
	update_option( OPTION_TEST_SUBSCRIPTIONS, $marked, false );
}

/**
 * Check whether a subscription was explicitly marked as a test import.
 *
 * @param string $subscription_id Subscription ID.
 * @return bool
 */
function is_marked( $subscription_id ) {
	$marked = get_option( OPTION_TEST_SUBSCRIPTIONS, array() );
	return isset( $marked[ (string) $subscription_id ] );
}

/**
 * Remove the test marker after a successful cleanup.
 *
 * @param string $subscription_id Subscription ID.
 * @return void
 */
function unmark( $subscription_id ) {
	$marked = get_option( OPTION_TEST_SUBSCRIPTIONS, array() );
	unset( $marked[ (string) $subscription_id ] );
	update_option( OPTION_TEST_SUBSCRIPTIONS, $marked, false );
}

/**
 * Get all posts and uniquely-owned attachments belonging to a subscription.
 *
 * @param string $subscription_id Subscription ID.
 * @return array{posts:int[],attachments:int[]}
 */
function get_plan( $subscription_id ) {
	$post_ids = get_posts(
		array(
			'post_type'              => 'any',
			'post_status'            => 'any',
			'posts_per_page'         => -1,
			'fields'                 => 'ids',
			'meta_key'               => META_SUBSCRIPTION,
			'meta_value'             => (string) $subscription_id,
			'orderby'                => 'ID',
			'order'                  => 'ASC',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		)
	);

	$attachment_ids = array();
	foreach ( $post_ids as $post_id ) {
		$thumbnail_id = (int) get_post_thumbnail_id( $post_id );
		if ( ! $thumbnail_id || attachment_is_used_outside_posts( $thumbnail_id, $post_ids ) ) {
			continue;
		}
		$attachment_ids[] = $thumbnail_id;
	}

	return array(
		'posts'       => array_map( 'intval', $post_ids ),
		'attachments' => array_values( array_unique( $attachment_ids ) ),
	);
}

/**
 * Check whether an attachment is referenced by a post outside the cleanup set.
 *
 * @param int   $attachment_id Attachment ID.
 * @param int[] $post_ids      Posts scheduled for deletion.
 * @return bool
 */
function attachment_is_used_outside_posts( $attachment_id, array $post_ids ) {
	$references = get_posts(
		array(
			'post_type'      => 'any',
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'post__not_in'   => $post_ids ?: array( 0 ),
			'meta_key'       => '_thumbnail_id',
			'meta_value'     => (int) $attachment_id,
			'no_found_rows'  => true,
		)
	);

	return ! empty( $references );
}

/**
 * Permanently delete a marked test import's local event content.
 *
 * @param string $subscription_id Subscription ID.
 * @return array{posts:int[],attachments:int[]}|\WP_Error
 */
function cleanup( $subscription_id ) {
	if ( 'local' !== wp_get_environment_type() ) {
		return new \WP_Error( 'jeero_test_cleanup_environment', 'Test cleanup is only available in the local WordPress environment.' );
	}

	if ( ! is_marked( $subscription_id ) ) {
		return new \WP_Error( 'jeero_test_cleanup_unmarked', 'The subscription is not marked as a Jeero guide-test import.' );
	}

	$plan = get_plan( $subscription_id );

	foreach ( $plan['posts'] as $post_id ) {
		if ( ! wp_delete_post( $post_id, true ) ) {
			return new \WP_Error( 'jeero_test_cleanup_post', sprintf( 'Could not delete post %d.', $post_id ) );
		}
	}

	foreach ( $plan['attachments'] as $attachment_id ) {
		if ( get_post( $attachment_id ) ) {
			\Jeero\Helpers\Images\safely_delete_attachment( $attachment_id );
		}
	}

	unmark( $subscription_id );
	return $plan;
}
