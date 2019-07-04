<?php
namespace Jeero\Db\Subscriptions;

function get_subscriptions() {
	
	global $wpdb;
	
	$sql = "SELECT * FROM {$wpdb->base_prefix}jeero_subscriptions";

	$result = $wpdb->get_results( $sql, ARRAY_A );
	
	if ( is_null( $result) ) {
		return $result;
	}
	
	$subscriptions = array();
	
	foreach( $result as $subscription ) {
		$subscription[ 'fields' ] = json_decode( $subscription[ 'fields' ], true );
		$subscription[ 'settings' ] = json_decode( $subscription[ 'settings' ], true );		
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
	
	$subscription[ 'fields' ] = json_decode( $subscription[ 'fields' ], true );
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
			'next_update' => NULL,
		)
	);
	
	$sql = "INSERT INTO {$wpdb->base_prefix}jeero_subscriptions 
		(ID, status, fields, settings, next_update) 
		VALUES ( %s, %s, %s, %s, %s ) 
		ON DUPLICATE KEY 
		UPDATE 
		status = VALUES( status ), 
		fields = VALUES( fields ), 
		settings = VALUES( settings ), 
		next_update = VALUES( next_update )";

	$sql = $wpdb->prepare( 
		$sql, 
		array(
			$ID,
			$data[ 'status' ],
			json_encode( $data[ 'fields' ] ),
			json_encode( $data[ 'settings' ] ),
			$data[ 'next_update' ],
		)
	);
	
	$wpdb->query( $sql );
	
}
