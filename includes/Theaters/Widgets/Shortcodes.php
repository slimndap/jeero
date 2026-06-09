<?php
/**
 * Theater widget shortcodes.
 */
namespace Jeero\Theaters\Widgets\Shortcodes;

use Jeero\Db;
use Jeero\Subscriptions\Subscription;
use Jeero\Theaters\Widgets\Widget;

const GENERIC_SHORTCODE = 'jeero_widget';
const SHORTCODE_PREFIX  = 'jeero_';

add_shortcode( GENERIC_SHORTCODE, __NAMESPACE__ . '\render_generic_shortcode' );

/**
 * Register the shortcode for a concrete theater widget.
 *
 * @since 1.34
 *
 * @param Widget $widget Theater widget.
 * @return void
 */
function register_widget_shortcode( Widget $widget ): void {

	add_shortcode(
		get_shortcode_tag_for_widget( $widget->get_name() ),
		__NAMESPACE__ . '\render_widget_shortcode'
	);

}

/**
 * Get the shortcode tag for a globally known widget name.
 *
 * @since 1.34
 *
 * @param string $widget_name Globally known widget name.
 * @return string
 */
function get_shortcode_tag_for_widget( string $widget_name ): string {

	return SHORTCODE_PREFIX . sanitize_key( $widget_name );

}

/**
 * Get an example shortcode for a globally known widget name.
 *
 * @since 1.34
 *
 * @param string $widget_name     Globally known widget name.
 * @param string $subscription_id Jeero subscription ID.
 * @return string
 */
function get_shortcode_example( string $widget_name, string $subscription_id = '' ): string {

	$shortcode = sprintf( '[%s', get_shortcode_tag_for_widget( $widget_name ) );

	if ( '' !== $subscription_id ) {
		$shortcode .= sprintf(
			' subscription="%s"',
			esc_attr( $subscription_id )
		);
	}

	return $shortcode . ']';

}

/**
 * Render the generic widget shortcode.
 *
 * @since 1.34
 *
 * @param array|string $atts Shortcode attributes.
 * @return string
 */
function render_generic_shortcode( $atts ): string {

	$atts        = normalize_shortcode_atts( $atts );
	$widget_name = get_widget_name_from_atts( $atts );

	return render_widget( $widget_name, $atts );

}

/**
 * Render a widget-specific shortcode.
 *
 * @since 1.34
 *
 * @param array|string $atts    Shortcode attributes.
 * @param string|null  $content Shortcode content.
 * @param string       $tag     Shortcode tag.
 * @return string
 */
function render_widget_shortcode( $atts, $content = null, string $tag = '' ): string {

	$atts        = normalize_shortcode_atts( $atts );
	$widget_name = get_widget_name_from_tag( $tag );

	return render_widget( $widget_name, $atts );

}

/**
 * Render a theater widget from placement attributes.
 *
 * Gutenberg blocks and menu items should call this lower-level function too,
 * instead of reimplementing shortcode parsing.
 *
 * @since 1.34
 *
 * @param string $widget_name Globally known widget name.
 * @param array  $atts        Placement attributes.
 * @return string
 */
function render_widget( string $widget_name, array $atts ): string {

	$widget_name  = sanitize_key( $widget_name );
	$subscription = get_subscription_from_atts( $atts );

	if ( '' === $widget_name || ! $subscription ) {
		return '';
	}

	return jeero_get_theater_widget(
		$widget_name,
		$subscription,
		get_widget_args_from_atts( $atts )
	);

}

/**
 * Normalize shortcode attributes to an array.
 *
 * @since 1.34
 *
 * @param array|string $atts Shortcode attributes.
 * @return array
 */
function normalize_shortcode_atts( $atts ): array {

	if ( ! is_array( $atts ) ) {
		return array();
	}

	return $atts;

}

/**
 * Get the widget name from generic shortcode attributes.
 *
 * @since 1.34
 *
 * @param array $atts Shortcode attributes.
 * @return string
 */
function get_widget_name_from_atts( array $atts ): string {

	foreach ( array( 'name', 'widget' ) as $key ) {
		if ( ! empty( $atts[ $key ] ) && is_scalar( $atts[ $key ] ) ) {
			return sanitize_key( (string) $atts[ $key ] );
		}
	}

	return '';

}

/**
 * Get the widget name from a widget-specific shortcode tag.
 *
 * @since 1.34
 *
 * @param string $tag Shortcode tag.
 * @return string
 */
function get_widget_name_from_tag( string $tag ): string {

	if ( 0 !== strpos( $tag, SHORTCODE_PREFIX ) ) {
		return '';
	}

	return sanitize_key( substr( $tag, strlen( SHORTCODE_PREFIX ) ) );

}

/**
 * Resolve the subscription for a widget placement.
 *
 * @since 1.34
 *
 * @param array $atts Placement attributes.
 * @return Subscription|null
 */
function get_subscription_from_atts( array $atts ) {

	foreach ( array( 'subscription', 'subscription_id', 'id' ) as $key ) {
		if ( ! empty( $atts[ $key ] ) && is_scalar( $atts[ $key ] ) ) {
			return new Subscription( sanitize_text_field( (string) $atts[ $key ] ) );
		}
	}

	$default_subscription_id = get_default_subscription_id();

	if ( '' === $default_subscription_id ) {
		return null;
	}

	return new Subscription( $default_subscription_id );

}

/**
 * Get the default subscription ID for placements without a subscription attribute.
 *
 * @since 1.34
 *
 * @return string
 */
function get_default_subscription_id(): string {

	$subscription_id = '';
	$subscriptions   = Db\Subscriptions\get_subscriptions();

	if ( 1 === count( $subscriptions ) ) {
		$subscription_id = (string) key( $subscriptions );
	}

	/**
	 * Filters the default subscription ID used by Jeero widget placements.
	 *
	 * @since 1.34
	 *
	 * @param string $subscription_id Default subscription ID.
	 */
	return (string) apply_filters(
		'jeero/theater_widgets/default_subscription_id',
		$subscription_id
	);

}

/**
 * Get render arguments from placement attributes.
 *
 * @since 1.34
 *
 * @param array $atts Placement attributes.
 * @return array
 */
function get_widget_args_from_atts( array $atts ): array {

	foreach ( array( 'name', 'widget', 'subscription', 'subscription_id', 'id' ) as $reserved_key ) {
		unset( $atts[ $reserved_key ] );
	}

	return $atts;

}
