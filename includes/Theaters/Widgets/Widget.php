<?php
/**
 * Base class for theater widgets.
 */
namespace Jeero\Theaters\Widgets;

use Jeero\Subscriptions\Subscription;

/**
 * Base class for renderable theater widgets.
 *
 * Theater source plugins can extend this class to register their implementation
 * for a globally known widget such as the cart indicator.
 *
 * @since 1.34
 */
abstract class Widget {

	/**
	 * Register the widget render and enqueue callbacks.
	 *
	 * @since 1.34
	 *
	 * @return void
	 */
	public function register(): void {

		add_filter(
			'jeero/theaters/widgets/render/' . $this->get_name(),
			array( $this, 'render' ),
			10,
			3
		);

		add_action(
			'jeero/theaters/widgets/enqueue/' . $this->get_name(),
			array( $this, 'enqueue' ),
			10,
			2
		);

	}

	/**
	 * Get the globally known widget name.
	 *
	 * @since 1.34
	 *
	 * @return string
	 */
	abstract public function get_name(): string;

	/**
	 * Render the widget for a subscription.
	 *
	 * @since 1.34
	 *
	 * @param string       $html         Existing widget HTML.
	 * @param Subscription $subscription Jeero subscription.
	 * @param array        $args         Render arguments.
	 * @return string
	 */
	abstract public function render( string $html, Subscription $subscription, array $args = array() ): string;

	/**
	 * Enqueue widget assets for a subscription.
	 *
	 * @since 1.34
	 *
	 * @param Subscription $subscription Jeero subscription.
	 * @param array        $args         Enqueue arguments.
	 * @return void
	 */
	public function enqueue( Subscription $subscription, array $args = array() ): void {}

}
