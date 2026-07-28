<?php
/**
 * Ticketmatic theater widget integration.
 */
namespace Jeero\Theaters;

use Jeero\Subscriptions\Subscription;

/**
 * Ticketmatic theater adapter.
 *
 * Signed widget URLs are generated locally from the subscription credentials
 * and the canonical Ticketmatic event URL imported by Jeero.
 *
 * @since 1.35
 */
class Ticketmatic extends Theater {

	public $display_name = 'Ticketmatic';

	/**
	 * Get the widgets supported by Ticketmatic v1.
	 *
	 * @return string[]
	 */
	public function get_supported_widgets(): ?array {

		return array( 'tickets_button', 'tickets_inline' );

	}

	/**
	 * Render the signed addtickets widget for the current event.
	 *
	 * Only presentation arguments are honored. In particular, a URL supplied
	 * through shortcode attributes can never replace canonical event data.
	 *
	 * @param Subscription $subscription Jeero subscription.
	 * @param array        $args         Render arguments.
	 * @return string
	 */
	public function get_tickets_inline_widget_html( Subscription $subscription, array $args = array() ): string {

		if ( ! $this->is_active_subscription( $subscription ) ) {
			return '';
		}

		$context = Widgets\get_ticket_context( $args, $subscription->ID );
		$url     = $this->get_tickets_inline_url( $subscription, $context );

		if ( '' === $url || ! $this->is_embeddable_widget_url( $url ) ) {
			return '';
		}

		$title  = ! empty( $args['title'] ) && is_scalar( $args['title'] ) ? (string) $args['title'] : __( 'Tickets', 'jeero' );
		$width  = ! empty( $args['width'] ) && is_scalar( $args['width'] ) ? (string) $args['width'] : '100%';
		$height = ! empty( $args['height'] ) ? absint( $args['height'] ) : 800;

		return sprintf(
			'<iframe src="%s" class="jeero-tickets-inline jeero-ticketmatic-addtickets" title="%s" loading="lazy" width="%s" height="%d"></iframe>',
			esc_url( $url ),
			esc_attr( $title ),
			esc_attr( $width ),
			$height
		);

	}

	/**
	 * Build a signed Ticketmatic addtickets URL from the canonical ticket URL.
	 *
	 * @param Subscription $subscription Jeero subscription.
	 * @param array        $context      Canonical ticket context.
	 * @return string
	 */
	protected function get_tickets_inline_url( Subscription $subscription, array $context ): string {

		$tickets_url = '';
		if ( ! empty( $context['is_event'] ) && ! empty( $context['post_id'] ) ) {
			$tickets_url = get_post_meta( absint( $context['post_id'] ), Widgets\META_TICKETS_URL, true );
		}

		$generated_url = $this->build_tickets_inline_url( $subscription, $tickets_url );

		if ( '' !== $generated_url ) {
			return $generated_url;
		}

		return ! empty( $context['tickets_inline_url'] ) && is_scalar( $context['tickets_inline_url'] )
			? esc_url_raw( (string) $context['tickets_inline_url'] )
			: '';

	}

	/**
	 * Build and sign a Ticketmatic addtickets URL.
	 *
	 * @param Subscription $subscription Jeero subscription.
	 * @param mixed        $tickets_url  Canonical Ticketmatic ticket URL.
	 * @return string
	 */
	protected function build_tickets_inline_url( Subscription $subscription, $tickets_url ): string {

		$account_name    = $subscription->get_setting( 'accountname' );
		$access_key      = $subscription->get_setting( 'accesskey' );
		$secret_key      = $subscription->get_setting( 'secretkey' );
		$return_url      = $subscription->get_setting( Widgets\Tickets_Inline::SETTING_RETURN_URL );
		$skin_id         = $subscription->get_setting( Widgets\Tickets_Inline::SETTING_SKIN_ID );
		$saleschannel_id = $subscription->get_setting( Widgets\Tickets_Inline::SETTING_SALESCHANNEL_ID );

		$required = array( $account_name, $access_key, $secret_key, $return_url, $skin_id, $tickets_url );
		foreach ( $required as $value ) {
			if ( ! is_scalar( $value ) || '' === trim( (string) $value ) ) {
				return '';
			}
		}

		$tickets_url = esc_url_raw( (string) $tickets_url );
		$return_url  = esc_url_raw( (string) $return_url );
		$parts        = wp_parse_url( $tickets_url );
		$return_parts = wp_parse_url( $return_url );

		if ( empty( $parts['scheme'] ) || 'https' !== strtolower( $parts['scheme'] ) || empty( $parts['host'] ) || empty( $parts['path'] ) || empty( $parts['query'] ) ) {
			return '';
		}

		if ( empty( $return_parts['scheme'] ) || ! in_array( strtolower( $return_parts['scheme'] ), array( 'http', 'https' ), true ) || empty( $return_parts['host'] ) ) {
			return '';
		}

		$account_name  = trim( (string) $account_name );
		$expected_path = sprintf( '/widgets/%s/addtickets', rawurlencode( $account_name ) );
		if ( $expected_path !== rtrim( $parts['path'], '/' ) ) {
			return '';
		}

		parse_str( $parts['query'], $ticket_parameters );
		if ( empty( $ticket_parameters['event'] ) || ! is_scalar( $ticket_parameters['event'] ) ) {
			return '';
		}

		$parameters = array(
			'event'        => sanitize_text_field( (string) $ticket_parameters['event'] ),
			'flow'         => 'basketwithcheckout',
			'oncompletion' => 'return',
			'returnurl'    => $return_url,
			'skinid'       => sanitize_text_field( (string) $skin_id ),
		);

		if ( is_scalar( $saleschannel_id ) && '' !== trim( (string) $saleschannel_id ) ) {
			$parameters['saleschannelid'] = sanitize_text_field( (string) $saleschannel_id );
		}

		ksort( $parameters );

		$payload = (string) $access_key . $account_name;
		foreach ( $parameters as $key => $value ) {
			$payload .= $key . $value;
		}

		$parameters['accesskey'] = trim( (string) $access_key );
		$parameters['signature'] = hash_hmac( 'sha256', $payload, (string) $secret_key );

		return sprintf(
			'https://%s%s?%s',
			strtolower( $parts['host'] ),
			$expected_path,
			http_build_query( $parameters, '', '&', PHP_QUERY_RFC3986 )
		);

	}

	/**
	 * Check whether a widget URL uses Ticketmatic or a sibling custom domain.
	 *
	 * @param string $url Signed Ticketmatic widget URL.
	 * @return bool
	 */
	protected function is_embeddable_widget_url( string $url ): bool {

		$widget_host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
		$site_host   = strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
		$allowed     = is_ssl() ? 'https' === wp_parse_url( $url, PHP_URL_SCHEME ) : in_array( wp_parse_url( $url, PHP_URL_SCHEME ), array( 'http', 'https' ), true );

		if ( $allowed && 'apps.ticketmatic.com' === $widget_host ) {
			$allowed = true;
		} elseif ( $allowed && '' !== $widget_host && '' !== $site_host ) {
			$site_domain = 0 === strpos( $site_host, 'www.' ) ? substr( $site_host, 4 ) : $site_host;
			$allowed     = $widget_host === $site_domain || $this->host_ends_with( $widget_host, '.' . $site_domain );
		} else {
			$allowed = false;
		}

		/**
		 * Filters whether a signed Ticketmatic widget URL may be embedded.
		 *
		 * @param bool   $allowed Whether the URL may be embedded.
		 * @param string $url     Signed Ticketmatic widget URL.
		 */
		return (bool) apply_filters( 'jeero/ticketmatic/widget_url_is_embeddable', $allowed, $url );

	}

	/**
	 * Check a host suffix without requiring PHP 8 string helpers.
	 *
	 * @param string $host   Hostname.
	 * @param string $suffix Expected suffix.
	 * @return bool
	 */
	protected function host_ends_with( string $host, string $suffix ): bool {

		return strlen( $host ) >= strlen( $suffix ) && substr( $host, -strlen( $suffix ) ) === $suffix;

	}
}
