<?php
/**
 * Theater widgets bootstrap.
 */
namespace Jeero\Theaters\Widgets;

use Jeero\Subscriptions\Subscription;

include_once \Jeero\PLUGIN_PATH . 'includes/Theaters/Widgets/Widget.php';
include_once \Jeero\PLUGIN_PATH . 'includes/Theaters/Widgets/Cart_Indicator.php';
include_once \Jeero\PLUGIN_PATH . 'includes/Theaters/Widgets/Cart_Inline.php';
include_once \Jeero\PLUGIN_PATH . 'includes/Theaters/Widgets/Tickets_Inline.php';
include_once \Jeero\PLUGIN_PATH . 'includes/Theaters/Widgets/Template_Functions.php';

/**
 * Register a concrete theater widget.
 *
 * @since 1.34
 *
 * @param Widget $widget Theater widget.
 * @return void
 */
function register_widget( Widget $widget ): void {

	$GLOBALS['jeero_theater_widgets'][ $widget->get_name() ] = $widget;

}

/**
 * Register that a theater supports a widget.
 *
 * @since 1.34
 *
 * @param string $theater_name Theater name or slug.
 * @param string $widget_name  Globally known widget name.
 * @return void
 */
function register_theater_widget_support( string $theater_name, string $widget_name ): void {

	$theater_name = sanitize_key( $theater_name );
	$widget_name  = sanitize_key( $widget_name );

	if ( '' === $theater_name || '' === $widget_name ) {
		return;
	}

	$GLOBALS['jeero_theater_widget_support'][ $theater_name ][ $widget_name ] = true;

}

/**
 * Get a registered theater widget.
 *
 * @since 1.34
 *
 * @param string $widget_name Globally known widget name.
 * @return Widget|null
 */
function get_widget( string $widget_name ) {

	$widget_name = sanitize_key( $widget_name );

	if ( empty( $GLOBALS['jeero_theater_widgets'][ $widget_name ] ) ) {
		return null;
	}

	return $GLOBALS['jeero_theater_widgets'][ $widget_name ];

}

/**
 * Check whether a theater supports a widget.
 *
 * @since 1.34
 *
 * @param string $theater_name Theater name or slug.
 * @param string $widget_name  Globally known widget name.
 * @return bool
 */
function theater_supports_widget( string $theater_name, string $widget_name ): bool {

	$theater_name = sanitize_key( $theater_name );
	$widget_name  = sanitize_key( $widget_name );

	if ( '' === $theater_name || '' === $widget_name ) {
		return false;
	}

	$theater = \Jeero\Theaters\get_theater( $theater_name );

	if ( $theater ) {
		$supported_widgets = $theater->get_supported_widgets();

		if ( null !== $supported_widgets ) {
			$supported_widgets = array_map( 'sanitize_key', $supported_widgets );

			return in_array( $widget_name, $supported_widgets, true );
		}
	}

	return ! empty( $GLOBALS['jeero_theater_widget_support'][ $theater_name ][ $widget_name ] );

}

/**
 * Get all widget names supported by a subscription's selected theater.
 *
 * @since 1.34
 *
 * @param Subscription $subscription Jeero subscription.
 * @return string[]
 */
function get_supported_widgets_for_subscription( Subscription $subscription ): array {

	$theater = $subscription->get( 'theater' );

	if ( is_array( $theater ) ) {
		$supported_widgets = get_supported_widgets_from_theater_metadata( $theater );

		if ( null !== $supported_widgets ) {
			return $supported_widgets;
		}

		if ( ! empty( $theater['name'] ) && is_scalar( $theater['name'] ) ) {
			return get_supported_widgets_for_theater( (string) $theater['name'] );
		}
	}

	$theater_name = $subscription->get_setting( 'theater' );

	if ( empty( $theater_name ) || ! is_scalar( $theater_name ) ) {
		return array();
	}

	return get_supported_widgets_for_theater( (string) $theater_name );

}

/**
 * Get all widget names supported by a theater name.
 *
 * @since 1.34
 *
 * @param string $theater_name Theater name or slug.
 * @return string[]
 */
function get_supported_widgets_for_theater( string $theater_name ): array {

	$theater_name = sanitize_key( $theater_name );

	if ( '' === $theater_name ) {
		return array();
	}

	$theater = \Jeero\Theaters\get_theater( $theater_name );

	if ( $theater ) {
		$supported_widgets = $theater->get_supported_widgets();

		if ( null !== $supported_widgets ) {
			return normalize_supported_widgets( $supported_widgets );
		}
	}

	if ( empty( $GLOBALS['jeero_theater_widget_support'][ $theater_name ] ) ) {
		return array();
	}

	return normalize_supported_widgets( $GLOBALS['jeero_theater_widget_support'][ $theater_name ] );

}

/**
 * Get the display label for a globally known widget name.
 *
 * @since 1.34
 *
 * @param string $widget_name Globally known widget name.
 * @return string
 */
function get_widget_label( string $widget_name ): string {

	$widget_name = sanitize_key( $widget_name );

	$labels = array(
		'cart_indicator' => __( 'Cart Indicator', 'jeero' ),
		'cart_inline'    => __( 'Inline Basket', 'jeero' ),
		'tickets_inline' => __( 'Inline Tickets', 'jeero' ),
	);

	if ( isset( $labels[ $widget_name ] ) ) {
		$label = $labels[ $widget_name ];
	} else {
		$label = ucwords( str_replace( '_', ' ', $widget_name ) );
	}

	return apply_filters( 'jeero/theater/widget/label', $label, $widget_name );

}

/**
 * Get supported widget names from theater metadata.
 *
 * @since 1.34
 *
 * @param array $theater Theater metadata.
 * @return string[]|null
 */
function get_supported_widgets_from_theater_metadata( array $theater ): ?array {

	foreach ( array( 'supported_widgets', 'widgets' ) as $key ) {
		if ( ! array_key_exists( $key, $theater ) ) {
			continue;
		}

		if ( ! is_array( $theater[ $key ] ) ) {
			return array();
		}

		return normalize_supported_widgets( $theater[ $key ] );
	}

	return null;

}

/**
 * Normalize a theater widget support list.
 *
 * @since 1.34
 *
 * @param array $widgets Widget list.
 * @return string[]
 */
function normalize_supported_widgets( array $widgets ): array {

	$supported_widgets = array();

	foreach ( $widgets as $key => $value ) {
		if ( is_string( $key ) ) {
			if ( ! $value ) {
				continue;
			}

			$widget_name = $key;
		} else {
			$widget_name = $value;
		}

		if ( ! is_scalar( $widget_name ) ) {
			continue;
		}

		$widget_name = sanitize_key( (string) $widget_name );

		if ( '' !== $widget_name ) {
			$supported_widgets[] = $widget_name;
		}
	}

	return array_values( array_unique( $supported_widgets ) );

}

new Cart_Indicator();
