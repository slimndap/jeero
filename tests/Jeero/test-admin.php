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

	function test_empty_subscriptions_shows_onboarding() {

		$actual = Admin\Subscriptions\get_admin_page_html();
		$expected = 'class="onboarding"';
		
		$this->assertContains( $expected, $actual );
		
	}

	function test_subscriptions_in_list_table() {

		add_filter( 'jeero/mother/get/response/endpoint=subscriptions', array( $this, 'get_mock_response_for_get_subscriptions' ), 10, 3 );
		
		$actual = Admin\Subscriptions\get_admin_page_html();
		$expected = 'a fake ID';
		
		$this->assertContains( $expected, $actual );
	}

	function test_edit_form() {
		
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

}
