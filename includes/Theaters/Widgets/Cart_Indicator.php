<?php
/**
 * Cart indicator widget.
 */
namespace Jeero\Theaters\Widgets;

use Jeero\Subscriptions\Subscription;

/**
 * Base class for theater source cart indicator widgets.
 *
 * @since 1.34
 */
class Cart_Indicator extends Widget {

	/**
	 * Get the globally known widget name.
	 *
	 * @since 1.34
	 *
	 * @return string
	 */
	public function get_name(): string {

		return 'cart_indicator';

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
	public function get_html( Subscription $subscription, array $args = array() ): string {
		$label = ! empty( $args['label'] ) ? $args['label'] : __( 'Cart', 'jeero' );
		$count = isset( $args['count'] ) ? (string) $args['count'] : '';
		$bind  = ! empty( $args['bind'] ) ? $args['bind'] : 'cart.count';
		$url   = $this->get_basket_url( $subscription, $args );

		$indicator = sprintf(
			'%s <span data-jeero-bind="%s">%s</span>',
			esc_html( $label ),
			esc_attr( $bind ),
			esc_html( $count )
		);

		if ( '' !== $url ) {
			$indicator = sprintf(
				'<a href="%s" class="jeero-cart-indicator">%s</a>',
				esc_url( $url ),
				$indicator
			);
		}

		return $indicator;

	}

	/**
	 * Get the basket URL.
	 *
	 * @since 1.34
	 *
	 * @param Subscription $subscription Jeero subscription.
	 * @param array        $args         Render arguments.
	 * @return string
	 */
	public function get_basket_url( Subscription $subscription, array $args = array() ): string {

		if ( ! empty( $args['basket_url'] ) ) {
			return esc_url_raw( $args['basket_url'] );
		}

		if ( ! empty( $args['url'] ) ) {
			return esc_url_raw( $args['url'] );
		}

		$integrations = $subscription->get( 'integrations' );

		if ( empty( $integrations ) || ! is_array( $integrations ) ) {
			return '';
		}

		foreach ( $integrations as $integration ) {
			if (
				! empty( $args['source'] )
				&& (
					empty( $integration['source'] )
					|| sanitize_key( $args['source'] ) !== sanitize_key( $integration['source'] )
				)
			) {
				continue;
			}

			if (
				empty( $integration['slot'] )
				|| 'cart_indicator' !== sanitize_key( $integration['slot'] )
			) {
				continue;
			}

			if ( ! empty( $integration['urls']['basket'] ) ) {
				return esc_url_raw( $integration['urls']['basket'] );
			}
		}

		return '';

	}

}
