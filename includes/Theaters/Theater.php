<?php
namespace Jeero\Theaters;

use Jeero\Subscriptions\Subscription;

/**
 * Theater class.
 * 
 */
class Theater {

	/**
	 * ID
	 * @var int ID
	 */
	public $ID;
	
	public $display_name;

	/**
	 * Get this theater's display label.
	 *
	 * @since 1.34
	 *
	 * @return string
	 */
	public function get_label(): string {

		if ( ! empty( $this->display_name ) && is_scalar( $this->display_name ) ) {
			return (string) $this->display_name;
		}

		return $this->get_name();

	}

	/**
	 * Get this theater's globally known name.
	 *
	 * @since 1.34
	 *
	 * @return string
	 */
	public function get_name(): string {

		$class_name = get_class( $this );
		$class_name = substr( $class_name, strrpos( $class_name, '\\' ) + 1 );

		return sanitize_key( $class_name );

	}

	/**
	 * Get the globally known widget names supported by this theater.
	 *
	 * Return null when the theater does not declare widget support. This keeps
	 * existing theater integrations compatible until they opt in explicitly.
	 *
	 * @since 1.34
	 *
	 * @return string[]|null
	 */
	public function get_supported_widgets(): ?array {

		return null;

	}

	/**
	 * Check whether there is an active subscription for this theater.
	 *
	 * @since 1.34
	 *
	 * @return bool
	 */
	public function has_active_subscription(): bool {

		$subscriptions = \Jeero\Subscriptions\get_subscriptions();

		if ( is_wp_error( $subscriptions ) ) {
			return false;
		}

		foreach ( $subscriptions as $subscription ) {
			if ( $subscription instanceof Subscription && $this->is_active_subscription( $subscription ) ) {
				return true;
			}
		}

		return false;

	}

	/**
	 * Check whether a subscription belongs to this theater and is active.
	 *
	 * @since 1.34
	 *
	 * @param Subscription $subscription Jeero subscription.
	 * @return bool
	 */
	protected function is_active_subscription( Subscription $subscription ): bool {

		$theater_name = $subscription->get_setting( 'theater' );

		if ( empty( $theater_name ) || ! is_scalar( $theater_name ) ) {
			return false;
		}

		$theater = get_theater( (string) $theater_name );

		if ( ! $theater || $this->get_name() !== $theater->get_name() ) {
			return false;
		}

		return ! $subscription->get( 'inactive' );

	}
	
}
