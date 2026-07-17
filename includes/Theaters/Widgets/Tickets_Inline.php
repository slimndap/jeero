<?php
/**
 * Inline tickets widget.
 */
namespace Jeero\Theaters\Widgets;

use Jeero\Subscriptions\Subscription;

/**
 * Base class for theater source inline tickets widgets.
 *
 * @since 1.34
 */
class Tickets_Inline extends Widget {

	const SETTING_RETURN_URL      = 'widgets/tickets_inline/return_url';
	const SETTING_SKIN_ID         = 'widgets/tickets_inline/skinid';
	const SETTING_SALESCHANNEL_ID = 'widgets/tickets_inline/saleschannelid';

	/**
	 * Get the globally known widget name.
	 *
	 * @since 1.34
	 *
	 * @return string
	 */
	public function get_name(): string {

		return 'tickets_inline';

	}

	/**
	 * Get the display label for the inline tickets widget.
	 *
	 * @since 1.34
	 *
	 * @return string
	 */
	public static function get_label(): string {

		return __( 'Inline Tickets', 'jeero' );

	}

	/**
	 * Get Ticketmatic settings sent to Jeero for signing widget URLs.
	 *
	 * @param Subscription $subscription Jeero subscription.
	 * @return array[]
	 */
	public function get_setting_fields( Subscription $subscription ): array {

		$theater = $subscription->get( 'theater' );
		$name    = ! empty( $theater['name'] ) ? $theater['name'] : $subscription->get_setting( 'theater' );

		if ( 'ticketmatic' !== sanitize_key( (string) $name ) ) {
			return array();
		}

		return array(
			array(
				'name'         => self::SETTING_RETURN_URL,
				'label'        => __( 'Ticketmatic return URL', 'jeero' ),
				'type'         => 'Url',
				'required'     => false,
				'instructions' => __( 'The page visitors return to after completing the Ticketmatic widget.', 'jeero' ),
			),
			array(
				'name'  => self::SETTING_SKIN_ID,
				'label' => __( 'Ticketmatic skin ID', 'jeero' ),
			),
			array(
				'name'  => self::SETTING_SALESCHANNEL_ID,
				'label' => __( 'Ticketmatic sales channel ID', 'jeero' ),
			),
			array(
				'type'  => 'Message',
				'name'  => 'widgets/tickets_inline/styling_note',
				'label' => __( 'Style the ticket flow in the Ticketmatic web skin. Jeero only controls the iframe wrapper, width and height.', 'jeero' ),
			),
		);

	}

}
