<?php
/**
 * Canonical ticket context helpers for theater widgets.
 */
namespace Jeero\Theaters\Widgets;

const META_TICKETS_URL        = 'jeero/import/post/tickets_url';
const META_TICKETS_STATUS     = 'jeero/import/post/tickets_status';
const META_TICKETS_INLINE_URL = 'jeero/import/post/widgets/tickets_inline/url';
const META_SUBSCRIPTION       = 'jeero/import/post/subscription';
const META_THEATER            = 'jeero/import/post/theater';

/**
 * Get the post ID that carries canonical Jeero ticket context.
 *
 * @since 1.35
 *
 * @param array $args Widget arguments.
 * @return int
 */
function get_ticket_context_post_id( array $args = array() ): int {

	foreach ( array( 'event_id', 'post_id' ) as $key ) {
		if ( ! empty( $args[ $key ] ) && is_scalar( $args[ $key ] ) ) {
			return absint( $args[ $key ] );
		}
	}

	if ( ! empty( $_GET['jeero_event'] ) && is_scalar( $_GET['jeero_event'] ) ) {
		return absint( wp_unslash( $_GET['jeero_event'] ) );
	}

	$post_id = absint( get_the_ID() );

	if ( ! $post_id ) {
		$post = get_post();

		if ( $post ) {
			$post_id = absint( $post->ID );
		}
	}

	if ( ! $post_id ) {
		$post_id = absint( get_queried_object_id() );
	}

	return $post_id;

}

/**
 * Get canonical ticket context from explicit args or Jeero post meta.
 *
 * @since 1.35
 *
 * @param array  $args            Widget arguments.
 * @param string $subscription_id Expected Jeero subscription ID.
 * @return array{post_id:int,tickets_url:string,tickets_inline_url:string,status:string,is_event:bool}
 */
function get_ticket_context( array $args = array(), string $subscription_id = '' ): array {

	$post_id                   = get_ticket_context_post_id( $args );
	$canonical_tickets_url     = '';
	$canonical_subscription_id = '';
	$tickets_url               = '';
	$tickets_inline_url        = '';
	$status                    = '';
	$is_event                  = false;

	if ( $post_id ) {
		$canonical_tickets_url = get_post_meta( $post_id, META_TICKETS_URL, true );
		$canonical_tickets_url = is_scalar( $canonical_tickets_url ) ? esc_url_raw( (string) $canonical_tickets_url ) : '';

		$canonical_subscription_id = get_post_meta( $post_id, META_SUBSCRIPTION, true );
		$canonical_subscription_id = is_scalar( $canonical_subscription_id ) ? sanitize_text_field( (string) $canonical_subscription_id ) : '';

		$is_event = '' !== $canonical_tickets_url
			&& '' !== $canonical_subscription_id
			&& '' !== $subscription_id
			&& $canonical_subscription_id === $subscription_id;
	}

	if ( ! empty( $args['tickets_url'] ) && is_scalar( $args['tickets_url'] ) ) {
		$tickets_url = esc_url_raw( (string) $args['tickets_url'] );
	} elseif ( $is_event ) {
		$tickets_url = $canonical_tickets_url;
	}

	if ( $is_event ) {
		$tickets_inline_url = get_post_meta( $post_id, META_TICKETS_INLINE_URL, true );
		$tickets_inline_url = is_scalar( $tickets_inline_url ) ? esc_url_raw( (string) $tickets_inline_url ) : '';
	}

	if ( isset( $args['status'] ) && is_scalar( $args['status'] ) ) {
		$status = normalize_ticket_status( (string) $args['status'] );
	} elseif ( $is_event ) {
		$status = get_post_meta( $post_id, META_TICKETS_STATUS, true );
		$status = is_scalar( $status ) ? normalize_ticket_status( (string) $status ) : '';
	}

	return array(
		'post_id'            => $post_id,
		'tickets_url'        => $tickets_url,
		'tickets_inline_url' => $tickets_inline_url,
		'status'             => $status,
		'is_event'           => $is_event,
	);

}

/**
 * Normalize a Jeero ticket status.
 *
 * @since 1.35
 *
 * @param string $status Ticket status.
 * @return string
 */
function normalize_ticket_status( string $status ): string {

	return sanitize_key( $status );

}
