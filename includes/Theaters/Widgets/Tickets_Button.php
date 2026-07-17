<?php
/**
 * Tickets button widget.
 */
namespace Jeero\Theaters\Widgets;

use Jeero\Subscriptions\Subscription;

/**
 * Generic event-scoped tickets button widget.
 *
 * @since 1.35
 */
class Tickets_Button extends Widget {

	const SETTING_TICKETS_PAGE = 'widgets/tickets_button/tickets_page';

	/**
	 * Get the globally known widget name.
	 *
	 * @since 1.35
	 *
	 * @return string
	 */
	public function get_name(): string {

		return 'tickets_button';

	}

	/**
	 * Get the display label for the tickets button widget.
	 *
	 * @since 1.35
	 *
	 * @return string
	 */
	public static function get_label(): string {

		return __( 'Tickets Button', 'jeero' );

	}

	/**
	 * Render the tickets button HTML.
	 *
	 * @since 1.35
	 *
	 * @param Subscription $subscription Jeero subscription.
	 * @param array        $args         Render arguments.
	 * @return string
	 */
	public function get_html( Subscription $subscription, array $args = array() ): string {

		$context = get_ticket_context( $args, $subscription->ID );
		$status  = '' !== $context['status'] ? $context['status'] : 'onsale';

		if ( 'hidden' === $status ) {
			return '';
		}

		$label = $this->get_label_for_status( $status, $args );

		if ( '' === $label ) {
			return '';
		}

		$classes = $this->get_button_classes( $status, $args );

		if ( ! in_array( $status, array( 'soldout', 'cancelled' ), true ) ) {
			$url = $this->get_button_url( $subscription, $context );

			if ( '' === $url ) {
				return '';
			}

			$target = ! empty( $args['target'] ) && is_scalar( $args['target'] ) ? (string) $args['target'] : '';

			return sprintf(
				'<a href="%s" class="%s" data-jeero-ticket-status="%s"%s>%s</a>',
				esc_url( $url ),
				esc_attr( $classes ),
				esc_attr( $status ),
				'' !== $target ? sprintf( ' target="%s"', esc_attr( $target ) ) : '',
				esc_html( $label )
			);
		}

		return sprintf(
			'<span class="%s" data-jeero-ticket-status="%s">%s</span>',
			esc_attr( $classes ),
			esc_attr( $status ),
			esc_html( $label )
		);

	}

	/**
	 * Tickets buttons are available for every Jeero subscription.
	 *
	 * @since 1.35
	 *
	 * @param Subscription $subscription Jeero subscription.
	 * @return bool
	 */
	public function supports_subscription( Subscription $subscription ): bool {

		return true;

	}

	/**
	 * Get settings fields for the tickets button widget.
	 *
	 * @since 1.35
	 *
	 * @param Subscription $subscription Jeero subscription.
	 * @return array[]
	 */
	public function get_setting_fields( Subscription $subscription ): array {

		return array(
			array(
				'name'    => self::SETTING_TICKETS_PAGE,
				'label'   => __( 'Tickets page', 'jeero' ),
				'type'    => 'select',
				'choices' => $this->get_tickets_page_choices(),
			),
		);

	}

	/**
	 * Get the button URL for an onsale ticket context.
	 *
	 * @since 1.35
	 *
	 * @param Subscription $subscription Jeero subscription.
	 * @param array        $context      Ticket context.
	 * @return string
	 */
	protected function get_button_url( Subscription $subscription, array $context ): string {

		$tickets_page_url = $this->get_tickets_page_url( $subscription );

		if ( '' !== $tickets_page_url && ! empty( $context['is_event'] ) ) {
			return esc_url_raw(
				add_query_arg(
					array(
						'jeero_event' => absint( $context['post_id'] ),
					),
					$tickets_page_url
				)
			);
		}

		return $context['tickets_url'];

	}

	/**
	 * Get the label for a ticket status.
	 *
	 * @since 1.35
	 *
	 * @param string $status Ticket status.
	 * @param array  $args   Render arguments.
	 * @return string
	 */
	protected function get_label_for_status( string $status, array $args ): string {

		switch ( $status ) {
			case 'soldout':
				return ! empty( $args['soldout_label'] ) ? (string) $args['soldout_label'] : __( 'Uitverkocht', 'jeero' );
			case 'cancelled':
				return ! empty( $args['cancelled_label'] ) ? (string) $args['cancelled_label'] : __( 'Geannuleerd', 'jeero' );
			default:
				return ! empty( $args['label'] ) ? (string) $args['label'] : __( 'Tickets', 'jeero' );
		}

	}

	/**
	 * Get the button classes.
	 *
	 * @since 1.35
	 *
	 * @param string $status Ticket status.
	 * @param array  $args   Render arguments.
	 * @return string
	 */
	protected function get_button_classes( string $status, array $args ): string {

		$classes = array(
			'jeero-tickets-button',
			sprintf( 'jeero-tickets-button--%s', sanitize_html_class( $status ) ),
		);

		if ( ! empty( $args['class'] ) && is_scalar( $args['class'] ) ) {
			$classes[] = (string) $args['class'];
		}

		return implode( ' ', array_filter( $classes ) );

	}

	/**
	 * Get available tickets page choices.
	 *
	 * @since 1.35
	 *
	 * @return string[]
	 */
	protected function get_tickets_page_choices(): array {

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
	 * Get the selected tickets page URL.
	 *
	 * @since 1.35
	 *
	 * @param Subscription $subscription Jeero subscription.
	 * @return string
	 */
	protected function get_tickets_page_url( Subscription $subscription ): string {

		$tickets_page = $subscription->get_setting( self::SETTING_TICKETS_PAGE );

		if ( empty( $tickets_page ) ) {
			return '';
		}

		$url = get_permalink( absint( $tickets_page ) );

		if ( ! $url ) {
			return '';
		}

		return esc_url_raw( $url );

	}
}
