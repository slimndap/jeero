<?php
/** Public campaign links based on imported ticketing references. */
namespace Jeero\Campaign_Links;

use Jeero\Calendars;

add_action( 'template_redirect', __NAMESPACE__ . '\\redirect', 1 );

/** Find published posts using the native reference fields, including pre-1.37 imports. */
function find_posts( $types, $key, $ref, $source_key = '', $sources = array() ) {
	$meta = array( array( 'key' => $key, 'value' => $ref, 'type' => 'BINARY' ) );
	if ( $source_key ) {
		$meta[] = array( 'key' => $source_key, 'value' => $sources, 'compare' => 'IN' );
	}
	return get_posts( array(
		'post_type' => $types, 'post_status' => 'publish', 'has_password' => false,
		'numberposts' => -1, 'orderby' => 'ID', 'order' => 'ASC', 'meta_query' => $meta,
	) );
}

/** A performance can have a different public page and booking context. Keep both IDs. */
function destination( $source, $kind, $ref ) {
	$matches = array();
	foreach ( Calendars\get_active_calendars() as $calendar ) {
		if ( ! $calendar instanceof Calendars\Post_Based_Calendar ) {
			continue;
		}
		$key = $calendar->get_ref_key( $source );
		if ( $calendar instanceof Calendars\Theater_For_WordPress ) {
			$posts = 'event' === $kind
				? find_posts( \WPT_Event::post_type_name, '_wpt_source_ref', $ref, '_wpt_source', array( $source, 'wpt_' . $source ) )
				: array_merge(
					find_posts( \WPT_Production::post_type_name, $key, $ref ),
					find_posts( \WPT_Production::post_type_name, '_wpt_source_ref', $ref, '_wpt_source', array( 'wpt_' . $source ) )
				);
		} elseif ( 'event' === $kind ) {
			$types = $calendar instanceof Calendars\Custom_Post_Type ? get_post_types( array( 'public' => true ) ) : $calendar->get_post_type();
			$posts = $calendar->get_posts_by_ref( $ref, $source, array(
				'post_type' => $types, 'post_status' => 'publish', 'has_password' => false,
				'numberposts' => -1, 'orderby' => 'ID', 'order' => 'ASC',
			), true );
		} elseif ( $calendar instanceof Calendars\The_Events_Calendar && $calendar->can_use_series() ) {
			$posts = find_posts( \TEC\Events_Pro\Custom_Tables\V1\Series\Post_Type::POSTTYPE, $key, $ref );
		} else {
			continue;
		}
		foreach ( $posts as $post ) {
			$page = $post;
			if ( $calendar instanceof Calendars\Theater_For_WordPress && 'event' === $kind ) {
				$page_id = get_post_meta( $post->ID, \WPT_Production::post_type_name, true );
				$page = $page_id ? get_post( $page_id ) : null;
				if ( ! $page || \WPT_Production::post_type_name !== $page->post_type ) {
					continue;
				}
			}
			if ( is_post_publicly_viewable( $page ) && '' === $page->post_password ) {
				$matches[ $post->ID ] = $page->ID;
			}
		}
	}
	if ( 1 !== count( $matches ) ) {
		return false;
	}
	$post_id = key( $matches );
	$page_id = current( $matches );
	/** Filters the local destination. The matched post ID identifies the specific performance for booking plugins. */
	return apply_filters( 'jeero/campaign_link/url', get_permalink( $page_id ), $post_id, $kind, $source, $ref, $page_id );
}

function redirect() {
	$request = null;
	foreach ( $_GET as $key => $value ) {
		if ( ! preg_match( '/^([a-z0-9_-]+)_(event|production)_ref$/D', $key, $parts ) ) {
			continue;
		}
		if ( $request || ! is_string( $value ) || '' === $value || strlen( $value ) > 191 ) {
			$request = false;
			break;
		}
		$request = array( $parts[1], $parts[2], wp_unslash( $value ) );
	}
	if ( null === $request || ! in_array( $_SERVER['REQUEST_METHOD'] ?? '', array( 'GET', 'HEAD' ), true ) ) {
		return;
	}
	$path = wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH );
	if ( untrailingslashit( (string) $path ) !== untrailingslashit( (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH ) ) ) {
		return;
	}
	$url = $request ? destination( ...$request ) : false;
	nocache_headers();
	if ( is_string( $url ) && wp_validate_redirect( $url, '' ) && wp_safe_redirect( $url, 302, 'Jeero' ) ) {
		exit;
	}
	wp_die( esc_html__( 'Event or production not found.', 'jeero' ), '', array( 'response' => 404 ) );
}
