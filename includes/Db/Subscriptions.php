<?php
namespace Jeero\Db\Subscriptions;

function get_settings() {
	
	global $wpdb;
	
	$sql = "SELECT ID, settings FROM {$wpdb->base_prefix}jeero_subscriptions";

	$result = $wpdb->get_results( $sql, ARRAY_A );

	if ( is_null( $result ) ) {
		return $result;
	}
	
	$settings = array();
	
	foreach( $result as $subscription ) {
		$settings[ $subscription[ 'ID' ] ] = json_decode( $subscription[ 'settings' ], true );
	}
	
	return $settings;
}

function get_subscriptions() {
	
	global $wpdb;
	
	$sql = "SELECT * FROM {$wpdb->base_prefix}jeero_subscriptions";

	$result = $wpdb->get_results( $sql, ARRAY_A );

	if ( is_null( $result) ) {
		return $result;
	}
	
	$subscriptions = array();
	
	foreach( $result as $subscription ) {
		$subscriptions[ $subscription[ 'ID' ] ] = $subscription;
	}
	
	return $subscriptions;
}

function get_subscription( $ID ) {
	
	global $wpdb;
	
	$sql = "SELECT * FROM {$wpdb->base_prefix}jeero_subscriptions
		WHERE ID = %s";
	$sql = $wpdb->prepare( $sql, $ID );
	
	$subscription = $wpdb->get_row( $sql, ARRAY_A );	
	
	if ( is_null( $subscription) ) {
		return $subscription;
	}
	
	$subscription[ 'settings' ] = json_decode( $subscription[ 'settings' ], true );
	
	return $subscription;
	
}

function save_subscription( $ID, $data ) {
	
	global $wpdb;
	
	$data = wp_parse_args( 
		$data, 
		array(
			'status' => '',
			'fields' => array(),
			'settings' => array(),
		)
	);
	
	$sql = "INSERT INTO {$wpdb->base_prefix}jeero_subscriptions 
		(`ID`, `settings` ) 
		VALUES ( %s, %s ) 
		ON DUPLICATE KEY 
		UPDATE 
		`settings` = VALUES( `settings` )";

	$sql = $wpdb->prepare( 
		$sql, 
		array(
			$ID,
			json_encode( $data[ 'settings' ] ),
		)
	);

	$wpdb->query( $sql );
	
}
