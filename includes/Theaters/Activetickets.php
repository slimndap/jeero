<?php
namespace Jeero\Theaters;

/**
 * Activetickets class.
 *
 * @extends Theater
 * @since 1.34
 */
class Activetickets extends Theater {

	/**
	 * Get the globally known widget names supported by ActiveTickets.
	 *
	 * @since 1.34
	 *
	 * @return string[]|null
	 */
	public function get_supported_widgets(): ?array {

		return array( 'cart_indicator' );

	}

}
