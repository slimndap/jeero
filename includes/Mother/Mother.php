<?php
/**
 * Sends Mother out to visit Theaters.
 */
namespace Jeero\Mother;

use Theaters\Theater;
use Jeero\Subscriptions\Subscription;
use Jeero\Db;

const BASE_URL = 'https://mother.jeero.ooo';

/**
 * Asks Mother to remind a Theater about a Subscription.
 * 
 * @since	1.0
 * @param	Subscription		$subscription	The Subscription.
 * @return  boolean|WP_Error	<true> if successful. An error if there was a problem.
 */
function check_status( Subscription $subscription ) {
	
}

/**
 * Asks Mother for a list of all Subscriptions.
 * 
 * @return	Subscription[]|WP_Error		All Subscriptions or an error if there is a problem.
 */
function get_subscriptions() {
	
	// Send request to Mother.
	$args = array(
		'site_url' => site_url(),
		'settings' => Db\Subscriptions\get_subscriptions(),
	);
	$answers = get( 'subscriptions', $args );	
	
	if ( is_wp_error( $answers ) ) {
		return $answers;
	}
	
	$subscriptions = array();
	foreach( $answers as $answer ) {
		$subscription = new Subscription( $answer[ 'ID' ] );
		$subscription->set( 'status', $answer[ 'status' ] );
		$subscription->set( 'fields', $answer[ 'fields' ] );
		$subscription->save();

		$subscriptions[ $subscription->get( 'ID' ) ] = $subscription;
	}
	
	return $subscriptions;
}

/**
 * Asks Mother to set up a new Subscription with a Theater.
 * 
 * @return	Subscription|WP_Error	The new Subscription or an error if there was a problem.
 */
function subscribe_me( ) {
	
	// Send request to Mother.
	$args = array(
		'site_url' => site_url(),
		'settings' => Db\Subscriptions\get_subscriptions(),
	);
	$answer = post( 'subscriptions', $args );
	
	if ( is_wp_error( $answer ) ) {
		return new \WP_Error( 'mother', sprintf( __( 'Failed to add a new subscription: %s.', 'jeero' ), $answer->get_error_message() ) );
	}
	
	$subscription = new Subscription( $answer[ 'ID' ] );
	$subscription->set( 'status', $answer[ 'status' ] );
	$subscription->set( 'fields', $answer[ 'fields' ] );
	$subscription->save();
	
	return $subscription;
}

function get( $endpoint, $data = array() ) {
	
	$response = apply_filters( 'jeero/mother/get/response', NULL, $endpoint, $data );

	if ( is_null( $response ) ) {

		$url = BASE_URL.'/'.$endpoint;
		
		if ( !empty( $data ) ) {
			$url = add_query_arg( $data, $url );
		}
		
		$response = wp_remote_get( $url );

	}

	if ( is_wp_error( $response ) ) {
		return $response;
	}
	
	return json_decode( wp_remote_retrieve_body( $response ), true );
	
}

function post( $endpoint, $data = array() ) {
	
	$response = apply_filters( 'jeero/mother/post/response', NULL, $endpoint, $data );

	if ( is_null( $response ) ) {

		$url = BASE_URL.'/'.$endpoint;
		
		if ( !empty( $data ) ) {
			
			$args = array(
				'body' => $data,
			);
			
		}
		
		$response = wp_remote_post( $url, $args );

	}

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	return json_decode( wp_remote_retrieve_body( $response ), true );	
	
}