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

	function test_activetickets_cart_inline_renders_cart_iframe_from_subscription_baseurl() {

		new \Jeero\Theaters\Widgets\Cart_Inline();

		$subscription = new Subscription( 'a fake ID' );
		$subscription->set(
			'theater',
			array(
				'name'              => 'activetickets',
				'supported_widgets' => array( 'cart_inline' ),
			)
		);
		$subscription->set(
			'settings',
			array(
				'baseurl' => 'https://tickets.example.com/shop',
			)
		);

		$actual = jeero_get_theater_widget(
			'cart_inline',
			$subscription,
			array(
				'title'  => 'Winkelmand',
				'height' => 640,
			)
		);

		$this->assertStringContainsString( 'class="jeero-theater-widget jeero-theater-widget--cart-inline jeero-theater-widget--theater-activetickets"', $actual );
		$this->assertStringContainsString( 'src="https://tickets.example.com/shop/Cart"', $actual );
		$this->assertStringContainsString( 'title="Winkelmand"', $actual );
		$this->assertStringContainsString( 'height="640"', $actual );

	}

	function test_cart_inline_does_not_render_for_non_activetickets_subscriptions() {

		new \Jeero\Theaters\Widgets\Cart_Inline();

		$subscription = new Subscription( 'a fake ID' );
		$subscription->set(
			'theater',
			array(
				'name'              => 'veezi',
				'supported_widgets' => array( 'cart_inline' ),
			)
		);
		$subscription->set(
			'settings',
			array(
				'baseurl' => 'https://tickets.example.com/shop',
			)
		);

		$actual = jeero_get_theater_widget( 'cart_inline', $subscription );

		$this->assertEquals( '', $actual );

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

		\Jeero\Db\Subscriptions\save_subscription(
			'a fake ID',
			array(
				'theater' => 'activetickets',
			)
		);

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

	function test_theater_widget_subscription_id_adds_supported_theater_wrapper_class() {

		\Jeero\Db\Subscriptions\save_subscription(
			'a fake ID',
			array(
				'theater' => 'activetickets',
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
			'class="jeero-theater-widget jeero-theater-widget--cart-indicator jeero-theater-widget--theater-activetickets"',
			$actual
		);

	}

	function test_theater_widget_returns_empty_string_when_subscription_theater_does_not_support_widget() {

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

		$this->assertEquals( '', $actual );

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

	function test_cart_indicator_uses_selected_cart_page_url() {

		$cart_page_id = wp_insert_post(
			array(
				'post_title'  => 'Basket',
				'post_status' => 'publish',
				'post_type'   => 'page',
			)
		);

		$subscription = new Subscription( 'a fake ID' );
		$subscription->set(
			'theater',
			array(
				'name'              => 'activetickets',
				'supported_widgets' => array( 'cart_indicator' ),
			)
		);
		$subscription->set(
			'settings',
			array(
				'widgets/cart_indicator/cart_page' => $cart_page_id,
			)
		);

		$actual = jeero_get_theater_widget(
			'cart_indicator',
			$subscription,
			array(
				'label' => 'Winkelmand',
			)
		);

		$this->assertStringContainsString( sprintf( 'href="%s"', get_permalink( $cart_page_id ) ), $actual );

	}

	function test_theater_widget_returns_empty_string_when_theater_metadata_does_not_support_widget() {

		$subscription = new Subscription( 'a fake ID' );
		$subscription->set(
			'theater',
			array(
				'name'              => 'activetickets',
				'supported_widgets' => array( 'tickets_inline' ),
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

		$this->assertEquals( '', $actual );

	}

	function test_theater_widget_renders_when_theater_metadata_supports_widget() {

		$subscription = new Subscription( 'a fake ID' );
		$subscription->set(
			'theater',
			array(
				'name'    => 'activetickets',
				'widgets' => array(
					'cart_indicator' => true,
				),
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

		$this->assertStringContainsString( 'Winkelmand', $actual );
		$this->assertStringContainsString( 'href="https://tickets.example.com/shop/"', $actual );

	}

	function test_theater_widget_registration_can_limit_supported_widgets_for_theater_setting() {

		\Jeero\Theaters\Widgets\register_theater_widget_support( 'test-theater-support', 'tickets_inline' );

		\Jeero\Db\Subscriptions\save_subscription(
			'a fake ID',
			array(
				'theater' => 'test-theater-support',
			)
		);

		$actual = jeero_get_theater_widget(
			'cart_indicator',
			'a fake ID',
			array(
				'label' => 'Winkelmand',
			)
		);

		$this->assertEquals( '', $actual );

	}

	function test_activetickets_supports_cart_indicator_through_theater_class() {

		\Jeero\Db\Subscriptions\save_subscription(
			'a fake ID',
			array(
				'theater' => 'activetickets',
			)
		);

		$actual = jeero_get_theater_widget(
			'cart_indicator',
			'a fake ID',
			array(
				'label' => 'Winkelmand',
			)
		);

		$this->assertStringContainsString( 'Winkelmand', $actual );

	}

	function test_widget_shortcode_renders_widget() {

		\Jeero\Db\Subscriptions\save_subscription(
			'a fake ID',
			array(
				'theater' => 'activetickets',
			)
		);

		$actual = do_shortcode( '[jeero_widget name="cart_indicator" subscription="a fake ID" label="Winkelmand"]' );

		$this->assertStringContainsString( 'Winkelmand', $actual );
		$this->assertStringContainsString( 'jeero-theater-widget--cart-indicator', $actual );

	}

	function test_registered_widget_gets_generated_shortcode() {

		\Jeero\Db\Subscriptions\save_subscription(
			'a fake ID',
			array(
				'theater' => 'activetickets',
			)
		);

		$this->assertTrue( shortcode_exists( 'jeero_cart_indicator' ) );

		$actual = do_shortcode( '[jeero_cart_indicator subscription="a fake ID" label="Winkelmand"]' );

		$this->assertStringContainsString( 'Winkelmand', $actual );
		$this->assertStringContainsString( 'jeero-theater-widget--cart-indicator', $actual );

	}

	function test_registered_cart_inline_widget_gets_generated_shortcode() {

		$this->assertTrue( shortcode_exists( 'jeero_cart_inline' ) );

	}

	function test_cart_shortcode_alias_is_not_registered() {

		$this->assertFalse( shortcode_exists( 'jeero_cart' ) );

	}

	function test_activetickets_does_not_support_unregistered_widgets() {

		\Jeero\Db\Subscriptions\save_subscription(
			'a fake ID',
			array(
				'theater' => 'activetickets',
			)
		);

		$actual = jeero_get_theater_widget( 'tickets_inline', 'a fake ID' );

		$this->assertEquals( '', $actual );

	}

	function test_theater_widget_returns_empty_string_without_subscription_theater() {

		$subscription = new Subscription( 'a fake ID' );

		$actual = jeero_get_theater_widget(
			'cart_indicator',
			$subscription,
			array(
				'label' => 'Winkelmand',
			)
		);

		$this->assertEquals( '', $actual );

	}

	function test_theater_widget_returns_empty_string_without_subscription() {

		$this->assertEquals( '', jeero_get_theater_widget( 'cart_indicator' ) );

	}

}
