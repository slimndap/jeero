<?php
/**
 * Account indicator widget.
 */
namespace Jeero\Theaters\Widgets;

/**
 * Base class for theater source account indicator widgets.
 *
 * @since 1.35
 */
class Account_Indicator extends Widget {

	/**
	 * Get the globally known widget name.
	 *
	 * @since 1.35
	 *
	 * @return string
	 */
	public function get_name(): string {

		return 'account_indicator';

	}

	/**
	 * Get the display label for the account indicator widget.
	 *
	 * @since 1.35
	 *
	 * @return string
	 */
	public static function get_label(): string {

		return __( 'Account Indicator', 'jeero' );

	}

}
