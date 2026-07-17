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

	const SETTING_CART_PAGE = 'widgets/cart_indicator/cart_page';

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
	 * Get the display label for the cart indicator widget.
	 *
	 * @since 1.34
	 *
	 * @return string
	 */
	public static function get_label(): string {

		return __( 'Cart Indicator', 'jeero' );

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
	 * Get settings fields for the cart indicator widget.
	 *
	 * @since 1.34
	 *
	 * @param Subscription $subscription Jeero subscription.
	 * @return array[]
	 */
	public function get_setting_fields( Subscription $subscription ): array {

		return array(
			array(
				'name'    => self::SETTING_CART_PAGE,
				'label'   => __( 'Cart page', 'jeero' ),
				'type'    => 'select',
				'choices' => $this->get_cart_page_choices(),
			),
		);

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

		$cart_page_url = $this->get_cart_page_url( $subscription );

		if ( '' !== $cart_page_url ) {
			return $cart_page_url;
		}

		return '';

	}

	/**
	 * Get available cart page choices.
	 *
	 * @since 1.34
	 *
	 * @return string[]
	 */
	protected function get_cart_page_choices(): array {

		$choices = array(
			'' => __( 'Select a page', 'jeero' ),
		);

		$pages = get_pages(
			array(
				'sort_column' => 'post_title',
			)
		);

		foreach ( $pages as $page ) {
			$choices[ (string) $page->ID ] = $page->post_title;
		}

		return $choices;

	}

	/**
	 * Get the selected cart page URL.
	 *
	 * @since 1.34
	 *
	 * @param Subscription $subscription Jeero subscription.
	 * @return string
	 */
	protected function get_cart_page_url( Subscription $subscription ): string {

		$cart_page = $subscription->get_setting( self::SETTING_CART_PAGE );

		if ( empty( $cart_page ) ) {
			return '';
		}

		$url = get_permalink( absint( $cart_page ) );

		if ( ! $url ) {
			return '';
		}

		return esc_url_raw( $url );

	}

}
