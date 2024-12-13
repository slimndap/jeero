<?php
/**
 * Handles all communication with the Jeero API (Mother).
 */
namespace Jeero\Mother;

use Theaters\Theater;
use Jeero\Subscriptions\Subscription;
use Jeero\Db;

const BASE_URL = 'https://ql621w5yfk.execute-api.eu-west-1.amazonaws.com/jeero/v1';
const SITE_KEY = 'jeero/mother/site_key';

/**
 * Sends a DELETE request to Mother.
 * 
 * @since	1.0
 * @param 	string			$endpoint
 * @param	array 			$data (default: array())
 * @return 	array|WP_Error
 */
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


/**
 * Gets the key for this website, used for authentication of API requests.
 * 
 * @since	1.0
 * @return	string	The key for this website.
 */
function get_site_key() {
	
	$site_key = get_option( SITE_KEY );
	
	// Generate site key if not present yet.
	if ( empty( $site_key ) ) {
		$site_key = uniqid();
		update_option( SITE_KEY, $site_key, false );
	}
	
	return $site_key;
	
}


/**
 * Gets the unique identifier for this website, used for authentication of API requests.
 *
 * The identifier is based on the site url and key.
 * 
 * @since	1.0
 * @return	string
 */
function get_site_identifier() {
	return site_url().'_'.get_site_key();	
}

function activate_subscription( $subscription_id ) {
	return get( 'subscriptions/'.$subscription_id.'/activate' );	
}

function deactivate_subscription( $subscription_id ) {
	return get( 'subscriptions/'.$subscription_id.'/deactivate' );	
}

/**
 * Gets the contents of the inbox.
 * 
 * @since	1.0
 * @since	1.10	Improved encoding of settings.
 *					Always return fully parsed items.
 * @since	1.21	Now uses POST to support settings > 1024kb.
 * @since	1.27	Added the $no_of_items_per_pickup param to specifiy the number of items in each inbox pickup.
 *
 * @param 	array			$settings				The setting values of all subscriptions.
 * @param	int|null		$no_of_items_per_pickup	The number of items in each inbox pickup.
 * @return	array|WP_Error
 */
function get_inbox( $settings, $no_of_items_per_pickup = null ) {

	$headers = array(
		'no_of_items_per_pickup' => $no_of_items_per_pickup,
	);

	$items = post( 'inbox/big', $settings, $headers );	
	
	if ( \is_wp_error( $items ) ) {
		return $items;
	}
	
	$event_defaults = array(
		'ref' => false,
		'start' => false,
		'end' => false,
		'tickets_url' => false,
		'prices' => array(),
		'venue' => false,
		'status' => 'onsale',
		'production' => false,
		'custom' => array(),
	);
	
	$production_defaults = array(
		'ref' => false,
		'title' => false,
		'description' => false,
		'img' => false,
		'categories' => array(),				
	);

	for( $i = 0; $i < count( $items ); $i++ ) {
		
		$data = $items[ $i ][ 'data' ];
		$data = wp_parse_args( $data, $event_defaults );
		if ( $data[ 'production' ] ) {
			$data[ 'production' ] = wp_parse_args( $data[ 'production' ], $production_defaults );
		}
		
		$items[ $i ][ 'data' ] = $data;

	}
	
	return $items;
	
}

/**
 * Removes items from the inbox.
 * 
 * @since	1.0
 * @param	string[]		$item_ids	The IDs of the inbox items.
 * @return	array|WP_Error
 */
function remove_inbox_items( $item_ids ) {

	$args = array(
		'inbox_id' => json_encode( $item_ids ),
	);
	return delete( 'inbox', $args );

}

/**
 * Gets a subscription.
 * 
 * @since	1.0
 * @since	1.10	Improved encoding of settings.
 * @param 	string	$subscription_id
 * @param	array	$settings			The settings for the subscription.
 * @return	array|WP_Error
 */
function get_subscription( $subscription_id, $settings ) {

	$args = array(
		'settings' => urlencode( json_encode( $settings, JSON_FORCE_OBJECT ) ),
	);

	return get( 'subscriptions/'.$subscription_id, $args );	

}

/**
 * Gets all subscriptions.
 * 
 * @since	1.0
 * @since	1.21	Now uses POST to support settings > 1024kb.
 *
 * @param 	array			$settings	The setting values of all subscriptions.
 * @return	array|WP_Error
 */
function get_subscriptions( $settings ) {

	$subscriptions = post( 'subscriptions/big', $settings );
	
	return $subscriptions;

}

/**
 * Creates a new subscription.
 * 
 * @since	1.0
 * @return	array|WP_Error
 */
function subscribe_me( ) {
	
	$answer = post( 'subscriptions' );

	if ( \is_wp_error( $answer ) ) {
		return new \WP_Error( 'mother', sprintf( __( 'Failed to add a new subscription: %s.', 'jeero' ), $answer->get_error_message() ) );
	}
	
	if ( empty( $answer[ 'id' ] ) ) {
		return new \WP_Error( 'mother', __( 'Failed to add a new subscription: incorrect response.', 'jeero' ) );		
	}
	
	return $answer;
}

/**
 * Updates an existing subscription.
 * 
 * @since	1.0
 * @param 	string			$subscription_id
 * @param	array			$settings			The settings for the subscription.
 * @return	array|WP_Error
 */
function update_subscription( $subscription_id, $settings ) {

	// Send request to Mother.
	$data = array(
		'settings' => $settings,
	);

	return post( 'subscriptions/'.$subscription_id, $data );	

}

/**
 * Sends a GET request to Mother.
 * 
 * @since	1.0
 * @since	1.27	Added the $headers param to specify addtional headers in the GET request.
 *
 * @param 	string			$endpoint
 * @param	array 			$params (default: array())
 * @param	array			$headers (default: array())
 * @return 	array|WP_Error
 */
function get( $endpoint, $params = array(), $headers = array() ) {
	
	$header_defaults = array(
		'site_url' => \site_url(),
		'site_key' => get_site_key(),		
	);
	$headers = wp_parse_args( $headers, $header_defaults );

	$response = apply_filters( 'jeero/mother/get/response', NULL, $endpoint, $params );
	$response = apply_filters( 'jeero/mother/get/response/endpoint='.$endpoint, $response, $endpoint, $params );

	if ( is_null( $response ) ) {

		$url = BASE_URL.'/'.$endpoint;
		
		if ( !empty( $params ) ) {
			$url = add_query_arg( $params, $url );
		}

		$args = array(
			'timeout' => 30,
			'headers' => $headers,
		);
		$response = wp_remote_get( $url, $args );

	}

	if ( \is_wp_error( $response ) ) {
		return new \WP_Error( $response->get_error_code(), $response->get_error_message() );
	}
	
	$body = json_decode( \wp_remote_retrieve_body( $response ), true );

	if ( 200 != \wp_remote_retrieve_response_code( $response ) ) {
		if ( empty( $body[ 'message' ] ) ) {
			return new \WP_Error( 'mother', \wp_remote_retrieve_body( $response ) );
		} else {
			return new \WP_Error( 'mother', $body[ 'message' ] );
		}
	}
	
	return $body;
	
}

/**
 * Sends a POST request to Mother.
 * 
 * @since	1.0
 * @since	1.27	Added the $headers param to specify addtional headers in the POST request.
 *
 * @param 	string			$endpoint
 * @param	array 			$data (default: array())
 * @param	array			$headers (default: array())
 * @return 	array|WP_Error
 */
function post( $endpoint, $data = array(), $headers= array() ) {
	
	$header_defaults = array(
		'site_url' => \site_url(),
		'site_key' => get_site_key(),		
	);
	$headers = wp_parse_args( $headers, $header_defaults );
	
	$response = \apply_filters( 'jeero/mother/post/response', NULL, $endpoint, $data, $headers );
	$response = \apply_filters( 'jeero/mother/post/response/endpoint='.$endpoint, $response, $endpoint, $data, $headers );

	if ( is_null( $response ) ) {

		$url = BASE_URL.'/'.$endpoint;
		
		$args = array(
			'timeout' => 30,
			'headers' => $headers,
		);
		
		if ( !empty( $data ) ) {
			$args[ 'body' ] = json_encode( $data );
		}
		
		$response = \wp_remote_post( $url, $args );
		
	}

	if ( is_wp_error( $response ) ) {
		return $response;
	}
	
	$body = json_decode( \wp_remote_retrieve_body( $response ), true );

	if ( 200 != \wp_remote_retrieve_response_code( $response ) ) {
		if ( empty( $body[ 'message' ] ) ) {
			return new \WP_Error( 'mother', \wp_remote_retrieve_body( $response ) );			
		} else {
			return new \WP_Error( 'mother', $body[ 'message' ] );
		}
	}

	return $body;	
	
}