<?php
namespace Jeero\Theaters;

use Jeero\Subscriptions\Subscription;

/**
 * Activetickets class.
 *
 * @extends Theater
 * @since 1.34
 */
class Activetickets extends Theater {

	/**
	 * Get the globally known widget names supported by ActiveTickets.
	 *
	 * @since 1.34
	 *
	 * @return string[]|null
	 */
	public function get_supported_widgets(): ?array {

		return array( 'cart_indicator', 'cart_inline' );

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
	public function get_cart_inline_widget_html( Subscription $subscription, array $args = array() ): string {

		$url = $this->get_cart_url( $subscription, $args );

		if ( '' === $url ) {
			return '';
		}

		$title  = ! empty( $args['title'] ) ? $args['title'] : __( 'Cart', 'jeero' );
		$width  = ! empty( $args['width'] ) ? $args['width'] : '100%';
		$height = ! empty( $args['height'] ) ? absint( $args['height'] ) : 800;

		return sprintf(
			'<iframe src="%s" class="jeero-cart-inline" title="%s" loading="lazy" width="%s" height="%d"></iframe>',
			esc_url( $url ),
			esc_attr( $title ),
			esc_attr( $width ),
			$height
		);

	}

	/**
	 * Get the cart URL.
	 *
	 * @since 1.34
	 *
	 * @param Subscription $subscription Jeero subscription.
	 * @param array        $args         Render arguments.
	 * @return string
	 */
	public function get_cart_url( Subscription $subscription, array $args = array() ): string {

		foreach ( array( 'cart_url', 'url' ) as $key ) {
			if ( ! empty( $args[ $key ] ) ) {
				return esc_url_raw( $args[ $key ] );
			}
		}

		if ( ! empty( $args['baseurl'] ) ) {
			return $this->get_cart_url_from_baseurl( (string) $args['baseurl'] );
		}

		$baseurl = $subscription->get_setting( 'baseurl' );

		if ( ! empty( $baseurl ) && is_scalar( $baseurl ) ) {
			return $this->get_cart_url_from_baseurl( (string) $baseurl );
		}

		return '';

	}

	/**
	 * Get the ActiveTickets cart URL from a subscription base URL.
	 *
	 * @since 1.34
	 *
	 * @param string $baseurl ActiveTickets base URL.
	 * @return string
	 */
	protected function get_cart_url_from_baseurl( string $baseurl ): string {

		$baseurl = esc_url_raw( $baseurl );

		if ( '' === $baseurl ) {
			return '';
		}

		return esc_url_raw( untrailingslashit( $baseurl ) . '/Cart' );

	}

}
