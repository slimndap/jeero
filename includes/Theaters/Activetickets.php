<?php
namespace Jeero\Theaters;

use Jeero\Subscriptions\Subscription;
use Jeero\Theaters\Widgets\Account_Indicator;

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

		return array( 'account_indicator', 'account_inline', 'cart_indicator', 'cart_inline', 'tickets_button', 'tickets_inline' );

	}

	/**
	 * Get the account indicator widget HTML.
	 *
	 * @since 1.35
	 *
	 * @param Subscription $subscription Jeero subscription.
	 * @param array        $args         Render arguments.
	 * @return string
	 */
	public function get_account_indicator_widget_html( Subscription $subscription, array $args = array() ): string {

		if ( ! $this->is_active_subscription( $subscription ) ) {
			return '';
		}

		$url = $this->get_account_indicator_url( $subscription, $args );

		if ( '' === $url ) {
			return '';
		}

		$label            = ! empty( $args['label'] ) ? $args['label'] : __( 'Account', 'jeero' );
		$logged_in_label  = ! empty( $args['logged_in_label'] ) ? $args['logged_in_label'] : $label;
		$logged_out_label = ! empty( $args['logged_out_label'] ) ? $args['logged_out_label'] : $label;

		return sprintf(
			'<a href="%s" class="jeero-account-indicator"><span data-jeero-bind="account.label" data-jeero-logged-in-label="%s" data-jeero-logged-out-label="%s">%s</span></a>',
			esc_url( $url ),
			esc_attr( $logged_in_label ),
			esc_attr( $logged_out_label ),
			esc_html( $label )
		);

	}

	/**
	 * Get the inline account widget HTML.
	 *
	 * @since 1.35
	 *
	 * @param Subscription $subscription Jeero subscription.
	 * @param array        $args         Render arguments.
	 * @return string
	 */
	public function get_account_inline_widget_html( Subscription $subscription, array $args = array() ): string {

		if ( ! $this->is_active_subscription( $subscription ) ) {
			return '';
		}

		$url = $this->get_account_url( $subscription, $args );

		if ( '' === $url ) {
			return '';
		}

		$title  = ! empty( $args['title'] ) ? $args['title'] : __( 'Account', 'jeero' );
		$width  = ! empty( $args['width'] ) ? $args['width'] : '100%';
		$height = ! empty( $args['height'] ) ? absint( $args['height'] ) : 800;

		return sprintf(
			'<iframe src="%s" class="jeero-account-inline" title="%s" loading="lazy" width="%s" height="%d"></iframe>',
			esc_url( $url ),
			esc_attr( $title ),
			esc_attr( $width ),
			$height
		);

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

		if ( ! empty( $args['cart_url'] ) ) {
			return $this->add_visitor_params_to_url( esc_url_raw( $args['cart_url'] ) );
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
	 * Get an ActiveTickets account URL.
	 *
	 * @since 1.35
	 *
	 * @param Subscription $subscription Jeero subscription.
	 * @param array        $args         Render arguments.
	 * @return string
	 */
	public function get_account_url( Subscription $subscription, array $args = array() ): string {

		if ( ! empty( $args['account_url'] ) ) {
			return esc_url_raw( $args['account_url'] );
		}

		$path = ! empty( $args['path'] ) ? (string) $args['path'] : '/nl-NL/Account/Manage';

		if ( ! empty( $args['baseurl'] ) ) {
			return $this->get_account_url_from_baseurl( (string) $args['baseurl'], $path );
		}

		$baseurl = $subscription->get_setting( 'baseurl' );

		if ( ! empty( $baseurl ) && is_scalar( $baseurl ) ) {
			return $this->get_account_url_from_baseurl( (string) $baseurl, $path );
		}

		return '';

	}

	/**
	 * Get the account indicator URL.
	 *
	 * @since 1.35
	 *
	 * @param Subscription $subscription Jeero subscription.
	 * @param array        $args         Render arguments.
	 * @return string
	 */
	public function get_account_indicator_url( Subscription $subscription, array $args = array() ): string {

		if ( ! empty( $args['account_url'] ) ) {
			return esc_url_raw( $args['account_url'] );
		}

		$account_page_url = $this->get_account_page_url( $subscription );

		if ( '' !== $account_page_url ) {
			return $account_page_url;
		}

		return $this->get_account_url( $subscription, $args );

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

		if ( ! empty( $args['tickets_url'] ) ) {
			return $this->add_visitor_params_to_url( esc_url_raw( $args['tickets_url'] ) );
		}

		$context = Widgets\get_ticket_context( $args );

		return $this->add_visitor_params_to_url( $context['tickets_url'] );

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
	 * Get an ActiveTickets account URL from a subscription base URL.
	 *
	 * @since 1.35
	 *
	 * @param string $baseurl ActiveTickets base URL.
	 * @param string $path    Account path.
	 * @return string
	 */
	protected function get_account_url_from_baseurl( string $baseurl, string $path ): string {

		$baseurl = esc_url_raw( $baseurl );

		if ( '' === $baseurl ) {
			return '';
		}

		$path = '/' . ltrim( $path, '/' );

		return esc_url_raw( untrailingslashit( $baseurl ) . $path );

	}

	/**
	 * Get the selected account page URL.
	 *
	 * @since 1.35
	 *
	 * @param Subscription $subscription Jeero subscription.
	 * @return string
	 */
	protected function get_account_page_url( Subscription $subscription ): string {

		$account_page = $subscription->get_setting( Account_Indicator::SETTING_ACCOUNT_PAGE );

		if ( empty( $account_page ) ) {
			return '';
		}

		$url = get_permalink( absint( $account_page ) );

		if ( ! $url ) {
			return '';
		}

		return esc_url_raw( $url );

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
