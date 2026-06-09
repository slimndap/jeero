<?php
/**
 * Theater widget template functions.
 */

if ( ! function_exists( 'jeero_get_theater_widget_subscription' ) ) {
	/**
	 * Resolve a theater widget subscription argument.
	 *
	 * @since 1.34
	 *
	 * @param mixed $subscription Jeero subscription object or subscription ID.
	 * @return \Jeero\Subscriptions\Subscription|null
	 */
	function jeero_get_theater_widget_subscription( $subscription ) {

		if ( $subscription instanceof \Jeero\Subscriptions\Subscription ) {
			return $subscription;
		}

		if ( is_scalar( $subscription ) && '' !== (string) $subscription ) {
			return new \Jeero\Subscriptions\Subscription( sanitize_text_field( (string) $subscription ) );
		}

		return null;

	}
}

if ( ! function_exists( 'jeero_get_theater_widget' ) ) {
	/**
	 * Get a rendered Jeero theater widget.
	 *
	 * @since 1.34
	 *
	 * @param string $widget_name  Globally known widget name.
	 * @param mixed  $subscription Jeero subscription object or subscription ID.
	 * @param array  $args         Render arguments.
	 * @return string
	 */
	function jeero_get_theater_widget( string $widget_name, $subscription = null, array $args = array() ): string {

		$subscription = jeero_get_theater_widget_subscription( $subscription );
		$widget_name  = sanitize_key( $widget_name );

		if ( ! $subscription || '' === $widget_name ) {
			return '';
		}

		do_action(
			'jeero/theaters/widgets/enqueue/' . $widget_name,
			$subscription,
			$args
		);

		return apply_filters(
			'jeero/theaters/widgets/render/' . $widget_name,
			'',
			$subscription,
			$args
		);

	}
}

if ( ! function_exists( 'jeero_theater_widget' ) ) {
	/**
	 * Display a rendered Jeero theater widget.
	 *
	 * @since 1.34
	 *
	 * @param string $widget_name  Globally known widget name.
	 * @param mixed  $subscription Jeero subscription object or subscription ID.
	 * @param array  $args         Render arguments.
	 * @return void
	 */
	function jeero_theater_widget( string $widget_name, $subscription = null, array $args = array() ): void {

		echo jeero_get_theater_widget( $widget_name, $subscription, $args );

	}
}
