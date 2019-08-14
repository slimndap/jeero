<?php
namespace Jeero\Db\Subscriptions;

const JEERO_OPTION_SUBSCRIPTION = 'jeero_subscriptions';

function get_settings() {
	
	$subscriptions = get_subscriptions();

	$settings = array();	
	foreach( $subscriptions as $subscription_id => $subscription ) {
		$settings[ $subscription_id ] = $subscription[ 'settings' ];
	}
	
	return $settings;

}

function get_subscriptions() {
	
	return get_option( JEERO_OPTION_SUBSCRIPTION, array() );

}

function get_subscription( $subscription_id ) {
	
	$subscriptions = get_subscriptions();
	
	if ( empty( $subscriptions[ $subscription_id ] ) ) {
		return false;
	}
	
	return $subscriptions[ $subscription_id ];
		
}

function save_subscription( $subscription_id, $data ) {
	
	$subscriptions = get_subscriptions();
	
	$subscriptions[ $subscription_id ] = array(
		'settings' => $data[ 'settings' ],
	);
	
	update_option( JEERO_OPTION_SUBSCRIPTION, $subscriptions, false );
		
}
