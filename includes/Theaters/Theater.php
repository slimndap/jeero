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

		$subscriptions = \Jeero\Db\Subscriptions\get_subscriptions();

		foreach ( $subscriptions as $subscription_data ) {
			if ( $this->is_active_subscription_data( $subscription_data ) ) {
				return true;
			}
		}

		return false;

	}

	/**
	 * Check whether local subscription data belongs to this theater and is active.
	 *
	 * @since 1.34
	 *
	 * @param array $subscription_data Local subscription data.
	 * @return bool
	 */
	protected function is_active_subscription_data( $subscription_data ): bool {

		if ( ! is_array( $subscription_data ) || ! empty( $subscription_data['inactive'] ) ) {
			return false;
		}

		$theater_name = '';

		if ( ! empty( $subscription_data['theater']['name'] ) && is_scalar( $subscription_data['theater']['name'] ) ) {
			$theater_name = (string) $subscription_data['theater']['name'];
		} elseif ( ! empty( $subscription_data['settings']['theater'] ) && is_scalar( $subscription_data['settings']['theater'] ) ) {
			$theater_name = (string) $subscription_data['settings']['theater'];
		}

		if ( '' === $theater_name ) {
			return false;
		}

		$theater = get_theater( $theater_name );

		return $theater && $this->get_name() === $theater->get_name();

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
