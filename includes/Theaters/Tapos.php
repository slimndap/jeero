<?php
namespace Jeero\Theaters;

use Jeero\Subscriptions\Subscription;

/** TaPoS checkout with a browser-owned session, safe for cached pages. */
class Tapos extends Theater {

	public $display_name = 'TaPoS';

	public function get_supported_widgets(): ?array {
		return array( 'tickets_button', 'tickets_inline' );
	}

	public function get_tickets_inline_widget_html( Subscription $subscription, array $args = array() ): string {
		$context = Widgets\get_ticket_context( $args, $subscription->ID );
		if ( ! $this->is_active_subscription( $subscription ) || ! $context['is_event'] || in_array( $context['status'], array( 'hidden', 'soldout', 'cancelled' ), true ) ) {
			return '';
		}

		// Only stored event data supplies the performance and fallback, never shortcode URLs.
		$direct = get_post_meta( $context['post_id'], Widgets\META_TICKETS_URL, true );
		$fallback = sprintf( '<p><a href="%s">%s</a></p>', esc_url( $direct ), esc_html__( 'Open ticket booking directly', 'jeero' ) );
		$base = $subscription->get_setting( 'baseurl' );
		$parts = is_string( $base ) ? wp_parse_url( $base ) : false;
		if ( ! $parts || ( $parts['scheme'] ?? '' ) !== 'https' || empty( $parts['host'] ) || empty( $parts['path'] ) || isset( $parts['user'] ) || isset( $parts['query'] ) || isset( $parts['fragment'] ) ) {
			return $fallback;
		}

		$source = $context['tickets_inline_url'] ?: $direct;
		parse_str( (string) wp_parse_url( $source, PHP_URL_QUERY ), $query );
		$code = $query['perfcode'] ?? '';
		if ( ! is_string( $code ) || ! preg_match( '/^[0-9]+$/D', $code ) ) {
			return $fallback;
		}
		$url = add_query_arg( array( 'perfcode' => $code, 'nodecorators' => 'true' ), $base );

		wp_enqueue_script( 'jeero/iframe-resizer', \Jeero\PLUGIN_URI . 'assets/js/vendor/iframeResizer.min.js', array(), '3.5.1', true );
		wp_enqueue_script( 'jeero/theaters/tapos', \Jeero\PLUGIN_URI . 'assets/js/theaters/Tapos.js', array( 'jeero/iframe-resizer' ), \Jeero\VERSION, true );

		return sprintf(
			'<div style="-webkit-overflow-scrolling:touch"><iframe data-jeero-tapos-url="%s" title="%s" width="100%%" height="700" style="border:0"></iframe></div>%s',
			esc_url( $url ), esc_attr__( 'Ticket booking', 'jeero' ), $fallback
		);
	}
}
