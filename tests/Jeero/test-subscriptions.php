<?php
use Jeero\Subscriptions;
use Jeero\Subscriptions\Subscription;

class Subscriptions_Test extends Jeero_Test {
	
	function test_get_subscriptions() {
		
		add_filter( 'jeero/mother/post/response/endpoint=subscriptions/big', array( $this, 'get_mock_response_for_get_subscriptions' ), 10, 3 );
		$actual = count( Subscriptions\get_subscriptions() );
		$expected = 2;
		
		$this->assertEquals( $expected, $actual );
		
	}

	function test_add_subscription() {

		add_filter( 'jeero/mother/post/response/endpoint=subscriptions', array( $this, 'get_mock_response_for_add_subscription' ), 10, 3 );

		$actual = Subscriptions\add_subscription();
		$expected = 'a fake ID';
		
		$this->assertEquals( $expected, $actual );
		
	}
	
	function test_add_subscription_response_is_processed() {

		add_filter( 'jeero/mother/post/response/endpoint=subscriptions', array( $this, 'get_mock_response_for_add_subscription' ), 10, 3 );

		$actual = Subscriptions\add_subscription();
		$expected = 'a fake ID';
		
		$this->assertEquals( $expected, $actual );
		
	}
	
	function test_subscription_fields_are_saved() {
		
		add_filter( 'jeero/mother/post/response/endpoint=subscriptions/big', array( $this, 'get_mock_response_for_get_subscriptions' ), 10, 3 );
		add_filter( 'jeero/mother/get/response/endpoint=subscriptions/a fake ID', array( $this, 'get_mock_response_for_get_subscription' ), 10, 3 );
		
		$subscription = Subscriptions\get_subscription( 'a fake ID' );
		$actual = $subscription->get_fields()[ 1 ]->get( 'name' );
		$expected = 'theater';
		
		$this->assertEquals( $expected, $actual );
		
	}

	function test_subscription_settings_are_saved() {
		
		add_filter( 'jeero/mother/post/response/endpoint=subscriptions/big', array( $this, 'get_mock_response_for_get_subscriptions' ), 10, 3 );
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
	
	function test_subscription_settings_are_filtered() {

		add_filter( 'jeero/mother/post/response/endpoint=subscriptions/big', array( $this, 'get_mock_response_for_get_subscriptions' ), 10, 3 );
		
		add_filter( 'jeero/subscription/settings', function( $settings, $subscription ) {
			foreach( $settings as $name => $setting ) {
				if ( 'field1' == $name ) {
					$setting = 'value_filtered';
				}
				$settings[ $name ] = $setting;
			}
			return $settings;
		}, 10, 3 );

		$subscriptions =  Subscriptions\get_subscriptions();
		
		$settings = array(
			'field1' => 'value_in_db',
		);
		
		$subscriptions[ 'a fake ID' ]->set( 'settings', $settings );
		$subscriptions[ 'a fake ID' ]->save();
		
		$subscription = new Subscription( 'a fake ID' );

		$actual = $subscription->get( 'settings' )[ 'field1' ];
		$expected = 'value_in_db';
		
		$actual = $subscription->get_setting( 'field1' );
		$expected = 'value_filtered';
		
		$this->assertEquals( $expected, $actual, print_r($actual, true) );
		
	}	
	
	function test_subscription_setting_is_filtered() {

		add_filter( 'jeero/mother/post/response/endpoint=subscriptions/big', array( $this, 'get_mock_response_for_get_subscriptions' ), 10, 3 );
		
		add_filter( 'jeero/subscription/setting/field1', function( $setting, $subscription ) {
			return 'value_filtered';	
		}, 10, 3 );

		$subscriptions =  Subscriptions\get_subscriptions();
		
		$settings = array(
			'field1' => 'value_in_db',
		);
		
		$subscriptions[ 'a fake ID' ]->set( 'settings', $settings );
		$subscriptions[ 'a fake ID' ]->save();
		
		$subscription = new Subscription( 'a fake ID' );

		$actual = $subscription->get( 'settings' )[ 'field1' ];
		$expected = 'value_in_db';
		
		$actual = $subscription->get_setting( 'field1' );
		$expected = 'value_filtered';
		
		$this->assertEquals( $expected, $actual, print_r($actual, true) );
		
	}
}
