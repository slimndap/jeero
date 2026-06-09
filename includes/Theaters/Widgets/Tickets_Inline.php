<?php
/**
 * Inline tickets widget.
 */
namespace Jeero\Theaters\Widgets;

/**
 * Base class for theater source inline tickets widgets.
 *
 * @since 1.34
 */
abstract class Tickets_Inline extends Widget {

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

}
