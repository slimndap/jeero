<?php
namespace Jeero\Account;

function get_fields() {
	
	$fields = array(
		array( 
			'type' => 'Tab',
			'label' => 'Account',
			'name' => 'account',
		),
		array( 
			'type' => 'text',
			'name' => 'account_firstname',
			'label' => __( 'First name', 'jeero' ),
		),
		array( 
			'type' => 'text',
			'name' => 'account_lastname',
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

