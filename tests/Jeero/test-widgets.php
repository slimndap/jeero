<?php
/**
 * @group widgets
 */

use Jeero\Subscriptions\Subscription;
use Jeero\Theaters\Widgets\Cart_Inline;
use Jeero\Theaters\Widgets\Tickets_Inline;

class Jeero_Test_Cart_Inline_Widget extends Cart_Inline {

	public function get_html( Subscription $subscription, array $args = array() ): string {

		return '';

	}

	public function get_public_wrapper_class(): string {

		return $this->get_wrapper_class();

	}

}

class Jeero_Test_Tickets_Inline_Widget extends Tickets_Inline {

	public function get_html( Subscription $subscription, array $args = array() ): string {

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

	function test_theater_widget_echoes_widget() {

		ob_start();
		jeero_theater_widget(
			'cart_indicator',
			'a fake ID',
			array(
				'label' => 'Winkelmand',
			)
		);
		$actual = ob_get_clean();

		$this->assertStringContainsString( 'Winkelmand', $actual );

	}

	function test_theater_widget_subscription_id_adds_theater_wrapper_class() {

		\Jeero\Db\Subscriptions\save_subscription(
			'a fake ID',
			array(
				'theater' => 'veezi',
			)
		);

		$actual = jeero_get_theater_widget(
			'cart_indicator',
			'a fake ID',
			array(
				'label' => 'Winkelmand',
			)
		);

		$this->assertStringContainsString(
			'class="jeero-theater-widget jeero-theater-widget--cart-indicator jeero-theater-widget--theater-veezi"',
			$actual
		);

	}

	function test_activetickets_cart_indicator_renders_output() {

		$subscription = new Subscription( 'a fake ID' );
		$subscription->set(
			'theater',
			array(
				'name' => 'activetickets',
			)
		);

		$actual = jeero_get_theater_widget(
			'cart_indicator',
			$subscription,
			array(
				'label' => 'Winkelmand',
				'url'   => 'https://tickets.example.com/shop/',
			)
		);

		$this->assertStringContainsString(
			'class="jeero-theater-widget jeero-theater-widget--cart-indicator jeero-theater-widget--theater-activetickets"',
			$actual
		);
		$this->assertStringContainsString( 'href="https://tickets.example.com/shop/"', $actual );
		$this->assertStringContainsString( 'Winkelmand', $actual );
		$this->assertStringContainsString( 'data-jeero-bind="cart.count"', $actual );

	}

	function test_activetickets_cart_indicator_renders_without_url() {

		$subscription = new Subscription( 'a fake ID' );

		$actual = jeero_get_theater_widget(
			'cart_indicator',
			$subscription,
			array(
				'label' => 'Winkelmand',
			)
		);

		$this->assertStringContainsString(
			'class="jeero-theater-widget jeero-theater-widget--cart-indicator"',
			$actual
		);
		$this->assertStringContainsString( 'Winkelmand', $actual );
		$this->assertStringContainsString( 'data-jeero-bind="cart.count"', $actual );
		$this->assertStringNotContainsString( '<a ', $actual );

	}

	function test_theater_widget_returns_empty_string_without_subscription() {

		$this->assertEquals( '', jeero_get_theater_widget( 'cart_indicator' ) );

	}

}
