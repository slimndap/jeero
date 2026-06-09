<?php
/**
 * Theater widgets bootstrap.
 */
namespace Jeero\Theaters\Widgets;

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
		return true;
	}

	$theater = \Jeero\Theaters\get_theater( $theater_name );

	if ( $theater ) {
		$supported_widgets = $theater->get_supported_widgets();

		if ( null !== $supported_widgets ) {
			$supported_widgets = array_map( 'sanitize_key', $supported_widgets );

			return in_array( $widget_name, $supported_widgets, true );
		}
	}

	if ( empty( $GLOBALS['jeero_theater_widget_support'][ $theater_name ] ) ) {
		return true;
	}

	return ! empty( $GLOBALS['jeero_theater_widget_support'][ $theater_name ][ $widget_name ] );

}

new Cart_Indicator();
