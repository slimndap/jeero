<?php
/**
 * Inline cart widget.
 */
namespace Jeero\Theaters\Widgets;

/**
 * Base class for theater source inline cart widgets.
 *
 * @since 1.34
 */
abstract class Cart_Inline extends Widget {

	/**
	 * Get the globally known widget name.
	 *
	 * @since 1.34
	 *
	 * @return string
	 */
	public function get_name(): string {

		return 'cart_inline';

	}

	/**
	 * Get the display label for the inline cart widget.
	 *
	 * @since 1.34
	 *
	 * @return string
	 */
	public static function get_label(): string {

		return __( 'Cart', 'jeero' );

	}

}
