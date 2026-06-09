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
