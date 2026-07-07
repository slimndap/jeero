<?php
/**
 * Account indicator widget.
 */
namespace Jeero\Theaters\Widgets;

use Jeero\Subscriptions\Subscription;

/**
 * Base class for theater source account indicator widgets.
 *
 * @since 1.35
 */
class Account_Indicator extends Widget {

	const SETTING_ACCOUNT_PAGE = 'widgets/account_indicator/account_page';

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

	/**
	 * Get settings fields for the account indicator widget.
	 *
	 * @since 1.35
	 *
	 * @param Subscription $subscription Jeero subscription.
	 * @return array[]
	 */
	public function get_setting_fields( Subscription $subscription ): array {

		return array(
			array(
				'name'    => self::SETTING_ACCOUNT_PAGE,
				'label'   => __( 'Account page', 'jeero' ),
				'type'    => 'select',
				'choices' => $this->get_account_page_choices(),
			),
		);

	}

	/**
	 * Get available account page choices.
	 *
	 * @since 1.35
	 *
	 * @return string[]
	 */
	protected function get_account_page_choices(): array {

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

}
