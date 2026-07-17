<?php
/**
 * Inline account widget.
 */
namespace Jeero\Theaters\Widgets;

/**
 * Base class for theater source inline account widgets.
 *
 * @since 1.35
 */
class Account_Inline extends Widget {

	/**
	 * Get the globally known widget name.
	 *
	 * @since 1.35
	 *
	 * @return string
	 */
	public function get_name(): string {

		return 'account_inline';

	}

	/**
	 * Get the display label for the inline account widget.
	 *
	 * @since 1.35
	 *
	 * @return string
	 */
	public static function get_label(): string {

		return __( 'Account', 'jeero' );

	}

}
