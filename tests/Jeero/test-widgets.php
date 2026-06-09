<?php
/**
 * @group widgets
 */

use Jeero\Subscriptions\Subscription;
use Jeero\Theaters\Widgets\Cart_Inline;
use Jeero\Theaters\Widgets\Tickets_Inline;

class Jeero_Test_Cart_Inline_Widget extends Cart_Inline {

	protected function get_html( Subscription $subscription, array $args = array() ): string {

		return '';

	}

	public function get_public_wrapper_class(): string {

		return $this->get_wrapper_class();

	}

}

class Jeero_Test_Tickets_Inline_Widget extends Tickets_Inline {

	protected function get_html( Subscription $subscription, array $args = array() ): string {

		return '';

	}

	public function get_public_wrapper_class(): string {

		return $this->get_wrapper_class();

	}

}

class Widgets_Test extends Jeero_Test {

	function test_cart_inline_widget_name_and_wrapper_class() {

		$widget = new Jeero_Test_Cart_Inline_Widget();

		$this->assertEquals( 'cart_inline', $widget->get_name() );
		$this->assertEquals(
			'jeero-theater-widget jeero-theater-widget--cart-inline',
			$widget->get_public_wrapper_class()
		);

	}

	function test_tickets_inline_widget_name_and_wrapper_class() {

		$widget = new Jeero_Test_Tickets_Inline_Widget();

		$this->assertEquals( 'tickets_inline', $widget->get_name() );
		$this->assertEquals(
			'jeero-theater-widget jeero-theater-widget--tickets-inline',
			$widget->get_public_wrapper_class()
		);

	}

}
