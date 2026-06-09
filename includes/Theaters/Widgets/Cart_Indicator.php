<?php
/**
 * Cart indicator widget.
 */
namespace Jeero\Theaters\Widgets;

/**
 * Base class for theater source cart indicator widgets.
 *
 * @since 1.34
 */
abstract class Cart_Indicator extends Widget {

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

}
