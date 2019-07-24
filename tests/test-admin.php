<?php
/**
 * @group admin
 */
 
use Jeero\Admin;
use Jeero\Subscriptions;

class Admin_Test extends WP_UnitTestCase {
	
	function setUp() {
		
		// Set hook suffix to prevent WP_List_Table from generating warnings.
		$GLOBALS['hook_suffix'] = '';		
	}
	
	function get_mock_response_for_add_subscription( $response, $endpoint, $args ) {
		
		if ( 'subscriptions' != $endpoint ) {
			return $response;
		}
		
		$body = array(
			'ID' => 'a fake ID',
			'status' => 'setup',
			'fields' => array(
				array(
					'name' => 'theater',
					'type' => 'select',
					'label' => 'Theater',
					'required' => true,
					'choices' => array(
						'veezi' => 'Veezi',
						'seatgeek' => 'Seatgeek',
						'stager' => 'Stager',	
					),
				),
			),
		);
		
		return array(
			'body' => json_encode( $body ),
			'response' => array(
				'code' => 200,
				'message' => 'OK',	
			),
		);
			
	}

	function get_mock_response_for_get_subscriptions( $response, $endpoint, $args ) {
		
		if ( 'subscriptions' != $endpoint ) {
			return $response;
		}
		
		$body = array(
			array(
				'ID' => 'a fake ID',
				'status' => 'setup',
				'fields' => array(
					array(
						'name' => 'theater',
						'type' => 'select',
						'label' => 'Theater',
						'required' => true,
						'choices' => array(
							'veezi' => 'Veezi',
							'seatgeek' => 'Seatgeek',
							'stager' => 'Stager',	
						),
					),
				),
			),			
			array(
				'ID' => 'another fake ID',
				'status' => 'active',
				'next_update' => time() + DAY_IN_SECONDS,
				'fields' => array(
					array(
						'name' => 'theater',
						'type' => 'select',
						'label' => 'Theater',
						'required' => true,
						'choices' => array(
							'veezi' => 'Veezi',
							'seatgeek' => 'Seatgeek',
							'stager' => 'Stager',	
						),
					),
				),
			),
		);
		
		return array(
			'body' => json_encode( $body ),
			'response' => array(
				'code' => 200,
				'message' => 'OK',	
			),
		);
			
	}
	
	function get_mock_response_for_get_subscription( $response, $endpoint, $args ) {
		
		if ( strpos( $endpoint, 'subscriptions/' !== 0 ) ) {
			return $response;
		}
		
		$body = array(
			'ID' => 'a fake ID',
			'status' => 'setup',
			'fields' => array(
				array(
					'name' => 'theater',
					'type' => 'select',
					'label' => 'Theater',
					'required' => true,
					'choices' => array(
						'veezi' => 'Veezi',
						'seatgeek' => 'Seatgeek',
						'stager' => 'Stager',	
					),
				),
			),
		);	
		
		return array(
			'body' => json_encode( $body ),
			'response' => array(
				'code' => 200,
				'message' => 'OK',	
			),
		);
			
	}

	function test_empty_subscriptions_shows_onboarding() {

		$actual = Admin\Subscriptions\get_admin_page_html();
		$expected = 'class="onboarding"';
		
		$this->assertContains( $expected, $actual );
		
	}

	function test_subscription_is_added() {
	}

	function test_subscriptions_in_list_table() {

		add_filter( 'jeero/mother/get/response', array( $this, 'get_mock_response_for_get_subscriptions' ), 10, 3 );
		
		$actual = Admin\Subscriptions\get_admin_page_html();
		$expected = 'a fake ID';
		
		$this->assertContains( $expected, $actual );
	}

	function test_edit_form() {
		
		add_filter( 'jeero/mother/get/response', array( $this, 'get_mock_response_for_get_subscription' ), 10, 3 );

		$_GET[ 'edit' ] = 'a fake ID';
		
		$actual = Admin\Subscriptions\get_admin_page_html();
		$expected = '<input type="hidden" name="subscription_id" value="a fake ID">';
		
		$this->assertContains( $expected, $actual );
		
	}

	function test_edit_form_has_fields() {

		add_filter( 'jeero/mother/get/response', array( $this, 'get_mock_response_for_get_subscription' ), 10, 3 );

		$_GET[ 'edit' ] = 'a fake ID';
		
		$actual = Admin\Subscriptions\get_admin_page_html();
		$expected = '<select name="theater"';
		
		$this->assertContains( $expected, $actual );

	}

}
