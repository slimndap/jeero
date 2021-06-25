<?php
/**
 * @group admin
 */
 
use Jeero\Admin;
use Jeero\Subscriptions;

class Admin_Test extends Jeero_Test {
	
	function setUp() {
		
		// Set hook suffix to prevent WP_List_Table from generating warnings.
		$GLOBALS['hook_suffix'] = '';		
		parent::setUp();
	}

	function test_empty_subscriptions_shows_edit_form() {

		add_filter( 'jeero/mother/get/response/endpoint=subscriptions', array( $this, 'get_mock_response_empty_array' ), 10, 3 );
		add_filter( 'jeero/mother/post/response/endpoint=subscriptions', array( $this, 'get_mock_response_for_add_subscription' ), 10, 3 );		
		add_filter( 'jeero/mother/get/response/endpoint=subscriptions/a fake ID', array( $this, 'get_mock_response_for_get_subscription' ), 10, 3 );

		$actual = Admin\Subscriptions\get_admin_page_html();
		$expected = '<input type="hidden" name="subscription_id" value="a fake ID">';
		
		$this->assertContains( $expected, $actual );
		
	}

	function test_subscriptions_in_list_table() {

		add_filter( 'jeero/mother/get/response/endpoint=subscriptions', array( $this, 'get_mock_response_for_get_subscriptions' ), 10, 3 );
		
		$actual = Admin\Subscriptions\get_admin_page_html();
		$expected = 'a fake ID';
		
		$this->assertContains( $expected, $actual );
	}

	function test_edit_form() {
		
		add_filter( 'jeero/mother/get/response/endpoint=subscriptions/a fake ID', array( $this, 'get_mock_response_for_get_subscription' ), 10, 3 );

		$_GET[ 'edit' ] = 'a fake ID';
		
		$actual = Admin\Subscriptions\get_admin_page_html();
		$expected = '<input type="hidden" name="subscription_id" value="a fake ID">';
		
		$this->assertContains( $expected, $actual );
		
	}

	function test_edit_form_has_fields() {

		add_filter( 'jeero/mother/get/response/endpoint=subscriptions/a fake ID', array( $this, 'get_mock_response_for_get_subscription' ), 10, 3 );

		$_GET[ 'edit' ] = 'a fake ID';
		
		$actual = Admin\Subscriptions\get_admin_page_html();
		$expected = '<input type="text" name="test_field"';
		
		$this->assertContains( $expected, $actual );

	}
	

    /**
	 * Tests if a subscription is updated after submitting the subscriptions form.
	 *
	 * @since	1.?
	 * @since	1.4		Run in isolation to avoid PHP warnings during test.
	 *					@see: https://github.com/sebastianbergmann/phpunit/issues/720#issuecomment-10421092
	 * @since	1.20		Dont run in isolation. No longer neccesary in WordPress 5.7.
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

    /**
	 * Tests if the subscriptions form is prefilled with subscription settings.
	 *
	 * @since	1.?
	 * @since	1.4		Run in isolation to avoid PHP warnings during test.
	 *					@see: https://github.com/sebastianbergmann/phpunit/issues/720#issuecomment-10421092
	 * @since	1.20		Dont run in isolation. No longer neccesary in WordPress 5.7.
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
		
		$this->assertContains( $expected, $actual );

	}
	
}
