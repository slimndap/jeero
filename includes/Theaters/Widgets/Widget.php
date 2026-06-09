<?php
/**
 * Base class for theater widgets.
 */
namespace Jeero\Theaters\Widgets;

use Jeero\Subscriptions\Subscription;

/**
 * Base class for renderable theater widgets.
 *
 * Theater source plugins can extend this class to register their implementation
 * for a globally known widget such as the cart indicator.
 *
 * @since 1.34
 */
abstract class Widget {

	/**
	 * Register this widget.
	 *
	 * @since 1.34
	 */
	public function __construct() {

		register_widget( $this );

	}

	/**
	 * Get the globally known widget name.
	 *
	 * @since 1.34
	 *
	 * @return string
	 */
	abstract public function get_name(): string;

	/**
	 * Render the widget wrapper for a subscription.
	 *
	 * @since 1.34
	 *
	 * @param Subscription $subscription Jeero subscription.
	 * @param array        $args         Render arguments.
	 * @return string
	 */
	final public function render( Subscription $subscription, array $args = array() ): string {

		if ( ! $this->supports_subscription( $subscription ) ) {
			return '';
		}

		$content = $this->get_html( $subscription, $args );

		if ( '' === $content ) {
			return '';
		}

		return sprintf(
			'<div class="%s">%s</div>',
			esc_attr( $this->get_wrapper_class( $subscription ) ),
			$content
		);

	}

	/**
	 * Get the widget HTML inside the wrapper.
	 *
	 * @since 1.34
	 *
	 * @param Subscription $subscription Jeero subscription.
	 * @param array        $args         Render arguments.
	 * @return string
	 */
	abstract public function get_html( Subscription $subscription, array $args = array() ): string;

	/**
	 * Check whether this widget is supported by a subscription's theater.
	 *
	 * @since 1.34
	 *
	 * @param Subscription $subscription Jeero subscription.
	 * @return bool
	 */
	public function supports_subscription( Subscription $subscription ): bool {

		$widget_name = $this->get_name();

		$theater = $subscription->get( 'theater' );
		if ( is_array( $theater ) ) {
			$supported_widgets = $this->get_supported_widgets_from_theater( $theater );

			if ( null !== $supported_widgets ) {
				return in_array( $widget_name, $supported_widgets, true );
			}

			if ( ! empty( $theater['name'] ) ) {
				return theater_supports_widget( (string) $theater['name'], $widget_name );
			}
		}

		$theater_name = $subscription->get_setting( 'theater' );

		if ( empty( $theater_name ) || ! is_scalar( $theater_name ) ) {
			return true;
		}

		return theater_supports_widget( (string) $theater_name, $widget_name );

	}

	/**
	 * Get the widget wrapper class attribute.
	 *
	 * @since 1.34
	 *
	 * @param Subscription|null $subscription Jeero subscription.
	 * @return string
	 */
	protected function get_wrapper_class( ?Subscription $subscription = null ): string {

		$classes = array(
			'jeero-theater-widget',
			sprintf(
				'jeero-theater-widget--%s',
				sanitize_html_class( str_replace( '_', '-', $this->get_name() ) )
			),
		);

		$theater_name = $this->get_theater_name( $subscription );

		if ( '' !== $theater_name ) {
			$classes[] = sprintf(
				'jeero-theater-widget--theater-%s',
				sanitize_html_class( $theater_name )
			);
		}

		return implode( ' ', $classes );

	}

	/**
	 * Get the supported widget names from theater metadata.
	 *
	 * @since 1.34
	 *
	 * @param array $theater Theater metadata.
	 * @return string[]|null
	 */
	protected function get_supported_widgets_from_theater( array $theater ): ?array {

		foreach ( array( 'supported_widgets', 'widgets' ) as $key ) {
			if ( ! array_key_exists( $key, $theater ) ) {
				continue;
			}

			if ( ! is_array( $theater[ $key ] ) ) {
				return array();
			}

			return $this->normalize_supported_widgets( $theater[ $key ] );
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
	protected function normalize_supported_widgets( array $widgets ): array {

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

	/**
	 * Get a sanitized theater name for wrapper classes.
	 *
	 * @since 1.34
	 *
	 * @param Subscription|null $subscription Jeero subscription.
	 * @return string
	 */
	protected function get_theater_name( ?Subscription $subscription = null ): string {

		if ( ! $subscription ) {
			return '';
		}

		$theater = $subscription->get( 'theater' );

		if ( ! empty( $theater['name'] ) ) {
			return sanitize_html_class(
				str_replace( '_', '-', $theater['name'] )
			);
		}

		$theater = $subscription->get_setting( 'theater' );

		if ( empty( $theater ) || ! is_scalar( $theater ) ) {
			return '';
		}

		return sanitize_html_class(
			str_replace( '_', '-', (string) $theater )
		);

	}

}
