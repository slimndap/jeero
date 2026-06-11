<?php
namespace Jeero\Theaters;

use Jeero\Subscriptions\Subscription;

add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\enqueue_activetickets_scripts', 0 );

/**
 * Enqueue ActiveTickets front-end scripts when an active subscription exists.
 *
 * @since 1.34
 *
 * @return void
 */
function enqueue_activetickets_scripts(): void {

	$theater = new Activetickets();

	if ( $theater->has_active_subscription() ) {
		wp_enqueue_script(
			'jeero/theaters/activetickets',
			\Jeero\PLUGIN_URI . 'assets/js/theaters/Activetickets.js',
			array(),
			\Jeero\VERSION,
			false
		);
	}

}

/**
 * Activetickets class.
 *
 * @extends Theater
 * @since 1.34
 */
class Activetickets extends Theater {

	public $display_name = 'ActiveTickets';

	/**
	 * Get the globally known widget names supported by ActiveTickets.
	 *
	 * @since 1.34
	 *
	 * @return string[]|null
	 */
	public function get_supported_widgets(): ?array {

		return array( 'cart_indicator', 'cart_inline', 'tickets_inline' );

	}

	/**
	 * Get the inline tickets widget HTML.
	 *
	 * @since 1.34
	 *
	 * @param Subscription $subscription Jeero subscription.
	 * @param array        $args         Render arguments.
	 * @return string
	 */
	public function get_tickets_inline_widget_html( Subscription $subscription, array $args = array() ): string {

		if ( ! $this->is_active_subscription( $subscription ) ) {
			return '';
		}

		$url = $this->get_tickets_url( $args );

		if ( '' === $url ) {
			return '';
		}

		$title  = ! empty( $args['title'] ) ? $args['title'] : __( 'Tickets', 'jeero' );
		$width  = ! empty( $args['width'] ) ? $args['width'] : '100%';
		$height = ! empty( $args['height'] ) ? absint( $args['height'] ) : 800;

		return sprintf(
			'<iframe src="%s" class="jeero-tickets-inline" title="%s" loading="lazy" width="%s" height="%d"></iframe>',
			esc_url( $url ),
			esc_attr( $title ),
			esc_attr( $width ),
			$height
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
	public function get_cart_inline_widget_html( Subscription $subscription, array $args = array() ): string {

		if ( ! $this->is_active_subscription( $subscription ) ) {
			return '';
		}

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
				return $this->add_visitor_params_to_url( esc_url_raw( $args[ $key ] ) );
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
	 * Get the ActiveTickets URL for an inline tickets widget.
	 *
	 * @since 1.34
	 *
	 * @param array $args Render arguments.
	 * @return string
	 */
	public function get_tickets_url( array $args = array() ): string {

		foreach ( array( 'tickets_url', 'url' ) as $key ) {
			if ( ! empty( $args[ $key ] ) ) {
				return $this->add_visitor_params_to_url( esc_url_raw( $args[ $key ] ) );
			}
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

		return $this->add_visitor_params_to_url(
			esc_url_raw( untrailingslashit( $baseurl ) . '/Cart' )
		);

	}

	/**
	 * Add ActiveTickets visitor params from the current URL to a cart URL.
	 *
	 * @since 1.34
	 *
	 * @param string $url Cart URL.
	 * @return string
	 */
	protected function add_visitor_params_to_url( string $url ): string {

		if ( '' === $url ) {
			return '';
		}

		$visitor_params = $this->get_visitor_url_params();

		if ( empty( $visitor_params ) ) {
			return $url;
		}

		return esc_url_raw( add_query_arg( $visitor_params, $url ) );

	}

	/**
	 * Get visitor params from the current URL.
	 *
	 * @since 1.34
	 *
	 * @return string[]
	 */
	protected function get_visitor_url_params(): array {

		$visitor_params = array();

		foreach ( array( 'visitorId', 'visitorLoginKey' ) as $key ) {
			if ( ! isset( $_GET[ $key ] ) || ! is_scalar( $_GET[ $key ] ) ) {
				continue;
			}

			$value = sanitize_text_field( wp_unslash( (string) $_GET[ $key ] ) );

			if ( '' !== $value ) {
				$visitor_params[ $key ] = $value;
			}
		}

		return $visitor_params;

	}

}
