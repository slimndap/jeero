<?php
/**
 * @group admin
 */
 
use Jeero\Admin;
use Jeero\Subscriptions;

class Admin_Test extends Jeero_Test {
	/**
	 * Tests if the MCP checkbox defaults to off and saves boolean values through Settings.
	 *
	 * @since	1.37
	 */
	function test_mcp_setting_defaults_to_off_and_renders_saved_checkbox_state() {
		Admin\Settings\register_settings();
		$settings = get_registered_settings()['jeero/enable_mcp'];
		$this->assertSame( 'jeero/settings/group', $settings['group'] );
		$this->assertSame( 'boolean', $settings['type'] );
		$this->assertFalse( get_option( 'jeero/enable_mcp' ) );
		ob_start();
		do_settings_sections( 'jeero/settings' );
		$html = ob_get_clean();
		$this->assertStringContainsString( 'Follow the Jeero MCP setup instructions', $html );
		$this->assertStringContainsString( 'href="https://jeero.ooo/mcp/"', $html );
		$this->assertLessThan( strpos( $html, 'https://jeero.ooo/mcp/' ), strpos( $html, 'MCP server' ) );
		$this->assertLessThan( strpos( $html, 'name="jeero/enable_mcp"' ), strpos( $html, 'https://jeero.ooo/mcp/' ) );
		foreach ( array( '1', null ) as $submitted ) {
			update_option( 'jeero/enable_mcp', $submitted );
			$this->assertSame( '1' === $submitted, get_option( 'jeero/enable_mcp' ) );
			ob_start();
			Admin\Settings\mcp_field();
			$html = ob_get_clean();
			$this->assertStringContainsString( 'name="jeero/enable_mcp"', $html );
			$this->assertSame( '1' === $submitted, false !== strpos( $html, 'checked=' ) );
		}
	}
	
	/**
	 * @dataProvider mcp_connection_states
	 */
	function test_mcp_connection_information( $enabled, $adapter, $permalinks ) {
		update_option( 'jeero/enable_mcp', $enabled );
		update_option( 'active_plugins', $adapter ? array( 'mcp-adapter/mcp-adapter.php' ) : array() );
		update_option( 'home', 'https://venue.example/wordpress' );
		update_option( 'siteurl', 'https://venue.example/wordpress' );
		update_option( 'permalink_structure', $permalinks );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		unset( $GLOBALS['wp_settings_fields']['jeero/settings'] );
		Admin\Settings\register_settings();
		ob_start();
		do_settings_sections( 'jeero/settings' );
		$html = ob_get_clean();
		if ( ! $enabled ) {
			$this->assertStringNotContainsString( 'jeero-mcp-url', $html );
			$this->assertStringNotContainsString( 'Get MCP Adapter', $html );
		} elseif ( ! $adapter ) {
			$this->assertStringContainsString( 'Jeero MCP requires the official WordPress MCP Adapter', $html );
			$this->assertStringContainsString( 'install it and activate it', $html );
			$this->assertStringNotContainsString( 'jeero-mcp-url', $html );
			$this->assertStringNotContainsString( 'Copy URL', $html );
		} else {
			$endpoint = $permalinks ? 'https://venue.example/wordpress/wp-json/mcp/mcp-adapter-default-server' : rest_url( 'mcp/mcp-adapter-default-server' );
			$this->assertStringContainsString( 'value="' . esc_attr( $endpoint ) . '"', $html );
			$this->assertStringContainsString( 'https://venue.example/wordpress/', $endpoint );
			$this->assertStringContainsString( 'mcp/mcp-adapter-default-server', $endpoint );
			$this->assertStringContainsString( '<th scope="row"><label for="jeero-mcp-url">Jeero MCP endpoint</label></th>', $html );
			$this->assertStringContainsString( 'class="regular-text code"', $html );
			$this->assertStringContainsString( 'Copy URL', $html );
			$this->assertStringContainsString( 'role="status"', $html );
			$this->assertStringNotContainsString( 'Connect your AI client using your WordPress username', $html );
			$this->assertStringNotContainsString( 'jeero-mcp-authentication', $html );
			$this->assertSame( 2, substr_count( $html, '<a href="https://jeero.ooo/mcp/">Follow the Jeero MCP setup instructions</a>' ) );
			$this->assertStringNotContainsString( 'application-passwords-section', $html );
		}
	}

	static function mcp_connection_states() {
		return array(
			'off without adapter' => array( false, false, '/%postname%/' ),
			'off with adapter' => array( false, true, '/%postname%/' ),
			'on without adapter' => array( true, false, '/%postname%/' ),
			'on with adapter in subdirectory' => array( true, true, '/%postname%/' ),
			'on with plain permalinks in subdirectory' => array( true, true, '' ),
		);
	}

	protected function setUp(): void {	
		
		// Set hook suffix to prevent WP_List_Table from generating warnings.
		$GLOBALS['hook_suffix'] = '';		
		parent::setUp();
	}

	function test_empty_subscriptions_shows_edit_form() {

		add_filter( 'jeero/mother/post/response/endpoint=subscriptions/big', array( $this, 'get_mock_response_empty_array' ), 10, 3 );
		add_filter( 'jeero/mother/post/response/endpoint=subscriptions', array( $this, 'get_mock_response_for_add_subscription' ), 10, 3 );		
		add_filter( 'jeero/mother/get/response/endpoint=subscriptions/a fake ID', array( $this, 'get_mock_response_for_get_subscription' ), 10, 3 );

		$actual = Admin\Subscriptions\get_admin_page_html();
		$expected = '<input type="hidden" name="subscription_id" value="a fake ID">';
		
		$this->assertStringContainsStringIgnoringCase( $expected, $actual );
		
	}

	function test_subscriptions_in_list_table() {

		add_filter( 'jeero/mother/post/response/endpoint=subscriptions/big', array( $this, 'get_mock_response_for_get_subscriptions' ), 10, 3 );
		
		$actual = Admin\Subscriptions\get_admin_page_html();
		$expected = 'a fake ID';
		
		$this->assertStringContainsStringIgnoringCase( $expected, $actual );
	}

	function test_subscriptions_list_ignores_stale_object_cache() {

		$list_table                = new Admin\Subscriptions\List_Table();
		$list_table->subscriptions = array();
		wp_cache_set( 'list_table', $list_table, 'Jeero\Admin\Subscriptions' );

		add_filter( 'jeero/mother/post/response/endpoint=subscriptions/big', array( $this, 'get_mock_response_for_get_subscriptions' ), 10, 3 );
		add_filter( 'jeero/mother/post/response/endpoint=subscriptions', array( $this, 'get_mock_response_for_add_subscription' ), 10, 3 );
		add_filter( 'jeero/mother/get/response/endpoint=subscriptions/a fake ID', array( $this, 'get_mock_response_for_get_subscription' ), 10, 3 );

		$actual = Admin\Subscriptions\get_admin_page_html();

		$this->assertStringContainsStringIgnoringCase( 'another fake ID', $actual );

	}

	function test_edit_form() {
		
		add_filter( 'jeero/mother/get/response/endpoint=subscriptions/a fake ID', array( $this, 'get_mock_response_for_get_subscription' ), 10, 3 );

		$_GET[ 'edit' ] = 'a fake ID';
		
		$actual = Admin\Subscriptions\get_admin_page_html();
		$expected = '<input type="hidden" name="subscription_id" value="a fake ID">';
		
		$this->assertStringContainsStringIgnoringCase( $expected, $actual );
		
	}

	function test_edit_form_has_fields() {

		add_filter( 'jeero/mother/get/response/endpoint=subscriptions/a fake ID', array( $this, 'get_mock_response_for_get_subscription' ), 10, 3 );

		$_GET[ 'edit' ] = 'a fake ID';
		
		$actual = Admin\Subscriptions\get_admin_page_html();
		$expected = '<input type="text" name="test_field"';
		
		$this->assertStringContainsStringIgnoringCase( $expected, $actual );

	}

	function test_edit_form_preserves_requested_tab() {

		add_filter( 'jeero/mother/get/response/endpoint=subscriptions/a fake ID', array( $this, 'get_mock_response_for_get_subscription' ), 10, 3 );

		$_GET = array(
			'edit'      => 'a fake ID',
			'jeero_tab' => 2,
		);

		$actual = Admin\Subscriptions\get_admin_page_html();

		$this->assertStringContainsStringIgnoringCase( '<input type="hidden" name="jeero_tab" value="2">', $actual );

	}

	function test_edit_form_has_widgets_tab_when_theater_supports_widgets() {

		$cart_page_id = wp_insert_post(
			array(
				'post_title'  => 'Basket',
				'post_status' => 'publish',
				'post_type'   => 'page',
			)
		);
		$account_page_id = wp_insert_post(
			array(
				'post_title'  => 'My Account',
				'post_status' => 'publish',
				'post_type'   => 'page',
			)
		);
		$tickets_page_id = wp_insert_post(
			array(
				'post_title'  => 'Tickets',
				'post_status' => 'publish',
				'post_type'   => 'page',
			)
		);

		add_filter(
			'jeero/mother/get/response/endpoint=subscriptions/a fake ID',
			function( $response, $endpoint, $args ) {
				$response = $this->get_mock_response_for_get_subscription( $response, $endpoint, $args );
				$body     = json_decode( $response['body'], true );

				$body['theater'] = array(
					'name'              => 'activetickets',
					'title'             => 'ActiveTickets',
					'custom_fields'     => array(),
					'supported_widgets' => array(
						'account_indicator',
						'cart_indicator',
						'cart_inline',
						'tickets_button',
					),
				);

				$response['body'] = json_encode( $body );

				return $response;
			},
			10,
			3
		);

		$_GET['edit'] = 'a fake ID';

		$actual = Admin\Subscriptions\get_admin_page_html();

		$this->assertStringContainsStringIgnoringCase( '<label>Widgets</label>', $actual );
		$this->assertStringContainsStringIgnoringCase( '<label>This theater support the following widgets</label>', $actual );
		$this->assertStringContainsStringIgnoringCase( '<li>Account Indicator <code>[jeero_account_indicator subscription=&quot;a fake ID&quot;]</code></li>', $actual );
		$this->assertStringContainsStringIgnoringCase( '<li>Cart Indicator <code>[jeero_cart_indicator subscription=&quot;a fake ID&quot;]</code></li>', $actual );
		$this->assertStringContainsStringIgnoringCase( '<li>Cart <code>[jeero_cart_inline subscription=&quot;a fake ID&quot;]</code></li>', $actual );
		$this->assertStringContainsStringIgnoringCase( '<li>Tickets Button <code>[jeero_tickets_button subscription=&quot;a fake ID&quot; tickets_url=&quot;https://example.com/tickets&quot;]</code></li>', $actual );
		$this->assertStringContainsStringIgnoringCase( '<label>Account page</label>', $actual );
		$this->assertStringContainsStringIgnoringCase( sprintf( '<option value="%s">My Account</option>', $account_page_id ), $actual );
		$this->assertStringContainsStringIgnoringCase( '<label>Cart page</label>', $actual );
		$this->assertStringContainsStringIgnoringCase( sprintf( '<option value="%s">Basket</option>', $cart_page_id ), $actual );
		$this->assertStringContainsStringIgnoringCase( '<label>Tickets page</label>', $actual );
		$this->assertStringContainsStringIgnoringCase( sprintf( '<option value="%s">Tickets</option>', $tickets_page_id ), $actual );

	}
	

    /**
	 * Tests if a subscription is updated after submitting the subscriptions form.
	 *
	 * @since	1.?
	 * @since	1.4		Run in isolation to avoid PHP warnings during test.
	 *					@see: https://github.com/sebastianbergmann/phpunit/issues/720#issuecomment-10421092
	 * @since	1.15.4	Dont run in isolation. No longer neccesary in WordPress 5.7.
     */
	function test_edit_form_submit_updates_subscription() {
		
		add_filter( 'jeero/mother/get/response/endpoint=subscriptions/a fake ID', array( $this, 'get_mock_response_for_get_subscription' ), 10, 3 );
		
		$_GET = array(
			'subscription_id' => 'a fake ID',
			'theater' => 'veezi',
			'test_field' => 'an updated value',
			'jeero/nonce' => wp_create_nonce( 'save' ),	
		);		
		
		Admin\Subscriptions\process_form( $_GET );
		
		$subscription = new Subscriptions\Subscription( 'a fake ID' );
		
		$actual = $subscription->get_setting( 'test_field' );
		$expected = 'an updated value';
		
		$this->assertEquals( $expected, $actual );
		
	}

	function test_edit_form_submit_redirect_preserves_active_tab() {

		add_filter( 'jeero/mother/get/response/endpoint=subscriptions/a fake ID', array( $this, 'get_mock_response_for_get_subscription' ), 10, 3 );

		$_GET = array(
			'subscription_id' => 'a fake ID',
			'theater'         => 'veezi',
			'test_field'      => 'an updated value',
			'jeero_tab'       => 2,
			'jeero/nonce'     => wp_create_nonce( 'save' ),
		);

		$redirect_url = Admin\Subscriptions\process_form();

		$this->assertStringContainsString( 'edit=a+fake+ID', $redirect_url );
		$this->assertStringContainsString( 'jeero_tab=2', $redirect_url );

	}

    /**
	 * Tests if the subscriptions form is prefilled with subscription settings.
	 *
	 * @since	1.?
	 * @since	1.4		Run in isolation to avoid PHP warnings during test.
	 *					@see: https://github.com/sebastianbergmann/phpunit/issues/720#issuecomment-10421092
	 * @since	1.15.4	Dont run in isolation. No longer neccesary in WordPress 5.7.
     */
	function test_edit_form_has_field_values() {

		add_filter( 'jeero/mother/get/response/endpoint=subscriptions/a fake ID', array( $this, 'get_mock_response_for_get_subscription' ), 10, 3 );

		$_GET = array(
			'subscription_id' => 'a fake ID',
			'test_field' => 'an updated value',
			'jeero/nonce' => wp_create_nonce( 'save' ),	
		);		
		Admin\Subscriptions\process_form( $_GET );
		
		$_GET = array(
			'edit' => 'a fake ID',
		);
		
		$actual = Admin\Subscriptions\get_admin_page_html();
		$expected = '<input type="text" name="test_field" value="an updated value"';
		
		$this->assertStringContainsStringIgnoringCase( $expected, $actual );

	}
	
}
