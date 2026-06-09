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

new Cart_Indicator();
