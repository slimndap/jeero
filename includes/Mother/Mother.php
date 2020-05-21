<?php
/**
 * Sends Mother out to visit Theaters.
 */
namespace Jeero\Mother;

use Theaters\Theater;
use Jeero\Subscriptions\Subscription;
use Jeero\Db;

const BASE_URL = 'https://ql621w5yfk.execute-api.eu-west-1.amazonaws.com/jeero/v1';
const SITE_KEY = 'jeero/mother/site_key';

function delete( $endpoint, $data = array() ) {
	
	$url = BASE_URL.'/'.$endpoint;
	
	if ( !empty( $data ) ) {
		$url = add_query_arg( $data, $url );
	}

	$args = array(
		'timeout' => 10,
		'method' => 'DELETE',
		'headers' => array(
			'site_url' => site_url(),
			'site_key' => get_site_key(),
		),
	);

	$response = wp_remote_request( $url, $args );	
}

function get_site_key() {
	
	$site_key = get_option( SITE_KEY );
	
	if ( empty( $site_key ) ) {
		$site_key = uniqid();
		update_option( SITE_KEY, $site_key, false );
	}
	
	return $site_key;
	
}

function get_site_identifier() {
	return site_url().'_'.get_site_key();	
}

function get_inbox( $settings ) {

	// Send request to Mother.
	$args = array(
		'settings' => json_encode( $settings ),
	);
	
	return get( 'inbox', $args );	
	
}

function remove_inbox_item( $ID ) {
	// Send request to Mother.
	$args = array(
		'site_url' => site_url(),
		'site_key' => get_site_key(),
		//'settings' => $settings,
	);
	return delete( 'inbox/'. $ID, $args );
}

function remove_inbox_items( $item_ids ) {
	// Send request to Mother.
	$args = array(
		'inbox_id' => json_encode( $item_ids ),
	);
	return delete( 'inbox', $args );
}

function get_subscription( $subscription_id, $settings ) {

	// Send request to Mother.
	$args = array(
		'settings' => json_encode( $settings, JSON_FORCE_OBJECT ),
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
		'settings' => urlencode( json_encode( $settings, JSON_FORCE_OBJECT ) ),
	);

	$subscriptions = get( 'subscriptions', $args );
	
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
		'settings' => Db\Subscriptions\get_subscriptions(),
	);
	$answer = post( 'subscriptions', $args );

	if ( is_wp_error( $answer ) ) {
		return new \WP_Error( 'mother', sprintf( __( 'Failed to add a new subscription: %s.', 'jeero' ), $answer->get_error_message() ) );
	}
	
	if ( empty( $answer[ 'id' ] ) ) {
		return new \WP_Error( 'mother', __( 'Failed to add a new subscription: incorrect response.', 'jeero' ) );		
	}
	
	return $answer;
}

function update_subscription( $subscription_id, $settings ) {

	// Send request to Mother.
	$data = array(
		'settings' => $settings,
	);

	return post( 'subscriptions/'.$subscription_id, $data );	

}

function get( $endpoint, $data = array() ) {
	
	$response = apply_filters( 'jeero/mother/get/response', NULL, $endpoint, $data );
	$response = apply_filters( 'jeero/mother/get/response/endpoint='.$endpoint, $response, $endpoint, $data );

	if ( is_null( $response ) ) {

		$url = BASE_URL.'/'.$endpoint;
		
		if ( !empty( $data ) ) {
			$url = add_query_arg( $data, $url );
		}

		$args = array(
			'timeout' => 30,
			'headers' => array(
				'site_url' => site_url(),
				'site_key' => get_site_key(),
			),
		);
		
		$response = wp_remote_get( $url, $args );

	}

	if ( is_wp_error( $response ) ) {
		return get_error( $response->get_error_code(), $response->get_error_message() );
	}
	
	$body = json_decode( wp_remote_retrieve_body( $response ), true );

	if ( 200 != wp_remote_retrieve_response_code( $response ) ) {
		if ( empty( $body[ 'message' ] ) ) {
			return get_error( 'mother', wp_remote_retrieve_body( $response ) );
		} else {
			return get_error( 'mother', $body[ 'message' ] );
		}
	}
	
	return $body;
	
}

function get_error( $code, $message ) {
	
	$error = new \WP_Error( $code, $message );
	do_action( 'qm/error', $error );
	
	return $error;
	
}

function post( $endpoint, $data = array() ) {
	
	$response = apply_filters( 'jeero/mother/post/response', NULL, $endpoint, $data );
	$response = apply_filters( 'jeero/mother/post/response/endpoint='.$endpoint, $response, $endpoint, $data );

	if ( is_null( $response ) ) {

		$url = BASE_URL.'/'.$endpoint;
		
		if ( !empty( $data ) ) {
			
			$args = array(
				'body' => json_encode( $data ),
				'headers' => array(
					'site_url' => site_url(),
					'site_key' => get_site_key(),
				),
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