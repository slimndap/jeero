<?php
namespace Jeero\Account;

function get_add_plan_url( string $subscription_id, int $limit, string $billing_cycle ) {
	$url = \add_query_arg( 'subscription_id', $subscription_id, \admin_url() );
	$url = \add_query_arg( 'limit', $limit, $url );
	$url = \add_query_arg( 'billing_cycle', $billing_cycle, $url ); 
	$url = \wp_nonce_url( $url, 'plan', 'jeero/nonce' );
	return $url;
}

function get_fields() {
	
	$fields = array(
		array( 
			'type' => 'Tab',
			'label' => 'Account',
			'name' => 'account',
		),
		array( 
			'type' => 'text',
			'name' => 'account_first_name',
			'label' => __( 'First name', 'jeero' ),
		),
		array( 
			'type' => 'text',
			'name' => 'account_last_name',
			'label' => __( 'Last name', 'jeero' ),
		),
		array( 
			'type' => 'text',
			'name' => 'account_email',
			'label' => __( 'Email', 'jeero' ),
		),
		array(
			'type' => 'Plan',
			'label' => 'Switch plan',
			'name' => 'plan',
		),
	);
	
	return $fields;
}

function get_plans() {
	
	return array(
		array(
			'limit' => 100,
			'monthly' => 25,
			'annually' => 240,
		),
		array(
			'limit' => 500,
			'monthly' => 35,
			'annually' => 360,
		),
	);
	
}

