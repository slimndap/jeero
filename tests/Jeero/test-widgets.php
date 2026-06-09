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

	function test_get_theater_widget_renders_widget() {

		$subscription = new Subscription( 'a fake ID' );

		add_action(
			'jeero/theaters/widgets/enqueue/cart_indicator',
			array( $this, 'record_widget_enqueue' ),
			10,
			2
		);

		add_filter(
			'jeero/theaters/widgets/render/cart_indicator',
			array( $this, 'render_test_widget' ),
			10,
			3
		);

		$actual = jeero_get_theater_widget(
			'cart_indicator',
			$subscription,
			array(
				'label' => 'Cart',
			)
		);

		$this->assertEquals( '<button>Cart:a fake ID</button>', $actual );
		$this->assertTrue( $this->widget_enqueued );

	}

	function test_theater_widget_echoes_widget() {

		add_filter(
			'jeero/theaters/widgets/render/cart_indicator',
			array( $this, 'render_test_widget' ),
			10,
			3
		);

		ob_start();
		jeero_theater_widget(
			'cart_indicator',
			'a fake ID',
			array(
				'label' => 'Cart',
			)
		);
		$actual = ob_get_clean();

		$this->assertEquals( '<button>Cart:a fake ID</button>', $actual );

	}

	function test_theater_widget_returns_empty_string_without_subscription() {

		$this->assertEquals( '', jeero_get_theater_widget( 'cart_indicator' ) );

	}

	public $widget_enqueued = false;

	function record_widget_enqueue( $subscription, $args ) {

		$this->widget_enqueued = $subscription instanceof Subscription && 'Cart' === $args[ 'label' ];

	}

	function render_test_widget( $html, $subscription, $args ) {

		return sprintf(
			'<button>%s:%s</button>',
			$args[ 'label' ],
			$subscription->ID
		);

	}

}
