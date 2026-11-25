<?php
/**
 * Resolves campaign links from ticketing references stored by Jeero and its calendars.
 *
 * @package Jeero
 */
namespace Jeero\Campaign_Links;

use Jeero\Calendars;

add_action( 'template_redirect', __NAMESPACE__ . '\\redirect', 1 );

/**
 * Finds published, password-free posts by an exact reference in calendar metadata.
 *
 * @param string|string[] $types      Post type or types to search.
 * @param string          $key        Meta key containing the reference.
 * @param string          $ref        Case-sensitive ticketing reference.
 * @param string          $source_key Optional meta key identifying the importer.
 * @param string[]        $sources    Accepted importer identifiers when a source key is supplied.
 * @return \WP_Post[] Matching posts ordered by ID.
 */
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

/**
 * Resolves a reference across active calendars and filters the destination of a unique match.
 *
 * @param string $source Ticketing source slug from the query parameter name.
 * @param string $kind   Reference kind, either 'event' or 'production'.
 * @param string $ref    Reference supplied by the ticketing source.
 * @return string|false Filtered destination URL, or false if no unique public match resolves.
 */
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
	/**
	 * Filters the destination before WordPress validates the redirect URL.
	 *
	 * For a Theater for WordPress event, a booking plugin can use the performance
	 * ID to build a ticket link even when the public permalink belongs to its production.
	 *
	 * @param string|false $url     Public page permalink, or false if unavailable.
	 * @param int          $post_id ID of the post matching the external reference.
	 * @param string       $kind    Reference kind, either 'event' or 'production'.
	 * @param string       $source Ticketing source slug.
	 * @param string       $ref    External reference from the request.
	 * @param int          $page_id ID of the post supplying the public permalink.
	 */
	return apply_filters( 'jeero/campaign_link/url', get_permalink( $page_id ), $post_id, $kind, $source, $ref, $page_id );
}

/**
 * Handles GET and HEAD campaign requests at the site home URL.
 *
 * A single <source>_event_ref or <source>_production_ref parameter selects
 * the destination. A valid destination receives a 302 redirect. An invalid
 * campaign request or an unresolved destination receives a 404 response.
 *
 * @return void
 */
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
