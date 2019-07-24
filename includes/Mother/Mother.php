<?php
/**
 * Sends Mother out to visit Theaters.
 */
namespace Jeero\Mother;

use Theaters\Theater;
use Jeero\Subscriptions\Subscription;
use Jeero\Db;

//const BASE_URL = 'https://mother.jeero.ooo/api/v1';
const BASE_URL = 'http://jeero.local/api/v1';

function get_inbox() {
	
	// Send request to Mother.
	$args = array(
		'site_url' => site_url(),
	);
	return get( 'inbox', $args );	
	
}

function get_subscription( $subscription_id, $settings ) {

	// Send request to Mother.
	$args = array(
		'site_url' => site_url(),
		'settings' => $settings,
	);
	return get( 'subscriptions/'.$subscription_id, $args );	

}

/**
 * Asks Mother for a list of all Subscriptions.
 * 
 * @return	Subscription[]|WP_Error		All Subscriptions or an error if there is a problem.
 */
function get_subscriptions( $settings ) {
	
	// Send request to Mother.
	$args = array(
		'site_url' => site_url(),
		'settings' => $settings,
	);
	return get( 'subscriptions', $args );	

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
	
	if ( empty( $answer[ 'ID' ] ) ) {
		return new \WP_Error( 'mother', __( 'Failed to add a new subscription: incorrect response.', 'jeero' ) );		
	}
	
	return $answer;
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
	
	$body = json_decode( wp_remote_retrieve_body( $response ), true );

	if ( 200 != wp_remote_retrieve_response_code( $response ) ) {
		if ( empty( $body[ 'message' ] ) ) {
			return new \WP_Error( 'mother', wp_remote_retrieve_body( $response ) );			
		} else {
			return new \WP_Error( 'mother', $body[ 'message' ] );
		}
	}

	return $body;	
	
}