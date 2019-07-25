<?php
use Jeero\Subscriptions;
use Jeero\Subscriptions\Subscription;

class Subscriptions_Test extends WP_UnitTestCase {
	
	
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
				'interval' => 3600,
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

	function test_get_subscriptions() {
		
		add_filter( 'jeero/mother/get/response', array( $this, 'get_mock_response_for_get_subscriptions' ), 10, 3 );
		$actual = count( Subscriptions\get_subscriptions() );
		$expected = 2;
		
		$this->assertEquals( $expected, $actual );
		
	}

	function test_add_subscription() {

		add_filter( 'jeero/mother/post/response', array( $this, 'get_mock_response_for_add_subscription' ), 10, 3 );

		$actual = Subscriptions\add_subscription();
		$expected = 'a fake ID';
		
		$this->assertEquals( $expected, $actual );
		
	}
	
	function test_add_subscription_response_is_processed() {

		add_filter( 'jeero/mother/post/response', array( $this, 'get_mock_response_for_add_subscription' ), 10, 3 );

		$actual = Subscriptions\add_subscription();
		$expected = 'a fake ID';
		
		$this->assertEquals( $expected, $actual );
		
	}
	
	function test_subscription_fields_are_saved() {
		
		add_filter( 'jeero/mother/get/response', array( $this, 'get_mock_response_for_get_subscriptions' ), 10, 3 );
		
		$subscription = Subscriptions\get_subscription( 'a fake ID' );

		$actual = $subscription->get_fields()[ 0 ]->get( 'name' );
		$expected = 'theater';
		
		$this->assertEquals( $expected, $actual );
		
	}

	function test_subscription_settings_are_saved() {
		
		add_filter( 'jeero/mother/get/response', array( $this, 'get_mock_response_for_get_subscriptions' ), 10, 3 );
		$subscriptions =  Subscriptions\get_subscriptions();
		
		$settings = array(
			'field1' => 'value1',
		);
		
		$subscriptions[ 'a fake ID' ]->set( 'settings', $settings );
		$subscriptions[ 'a fake ID' ]->save();
		
		$subscription = new Subscription( 'a fake ID' );

		$actual = $subscription->get( 'settings' )[ 'field1' ];
		$expected = 'value1';
		
		$this->assertEquals( $expected, $actual, print_r($actual, true) );
		
	}
}
