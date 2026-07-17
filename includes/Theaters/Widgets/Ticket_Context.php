<?php
/**
 * Canonical ticket context helpers for theater widgets.
 */
namespace Jeero\Theaters\Widgets;

const META_TICKETS_URL    = 'jeero/import/post/tickets_url';
const META_TICKETS_STATUS = 'jeero/import/post/tickets_status';
const META_SUBSCRIPTION   = 'jeero/import/post/subscription';
const META_THEATER        = 'jeero/import/post/theater';

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
 * @param array $args Widget arguments.
 * @return array{post_id:int,tickets_url:string,status:string}
 */
function get_ticket_context( array $args = array() ): array {

	$post_id     = get_ticket_context_post_id( $args );
	$tickets_url = '';
	$status      = '';

	if ( ! empty( $args['tickets_url'] ) && is_scalar( $args['tickets_url'] ) ) {
		$tickets_url = esc_url_raw( (string) $args['tickets_url'] );
	} elseif ( $post_id ) {
		$tickets_url = get_post_meta( $post_id, META_TICKETS_URL, true );
		$tickets_url = is_scalar( $tickets_url ) ? esc_url_raw( (string) $tickets_url ) : '';
	}

	if ( isset( $args['status'] ) && is_scalar( $args['status'] ) ) {
		$status = normalize_ticket_status( (string) $args['status'] );
	} elseif ( $post_id ) {
		$status = get_post_meta( $post_id, META_TICKETS_STATUS, true );
		$status = is_scalar( $status ) ? normalize_ticket_status( (string) $status ) : '';
	}

	return array(
		'post_id'     => $post_id,
		'tickets_url' => $tickets_url,
		'status'      => $status,
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
