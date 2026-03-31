<?php
/**
 * @group mother
 */

use Jeero\Mother;

class Mother_Test extends Jeero_Test {

	function test_get_retries_with_fallback_url_after_dns_failure() {

		$requested_urls = array();
		$http_mock      = function( $preempt, $args, $url ) use ( &$requested_urls ) {
			$requested_urls[] = $url;

			if ( strpos( $url, Mother\BASE_URL . '/subscriptions/test' ) === 0 ) {
				return new WP_Error( 'http_request_failed', 'cURL error 6: Could not resolve host: api.jeero.ooo' );
			}

			if ( strpos( $url, Mother\FALLBACK_BASE_URL . '/subscriptions/test' ) === 0 ) {
				return array(
					'body'     => wp_json_encode( array( 'status' => 'ok' ) ),
					'response' => array(
						'code'    => 200,
						'message' => 'OK',
					),
				);
			}

			return $preempt;
		};
		add_filter( 'pre_http_request', $http_mock, 10, 3 );

		$result = Mother\get( 'subscriptions/test' );

		remove_filter( 'pre_http_request', $http_mock, 10 );

		$this->assertSame( array( 'status' => 'ok' ), $result );
		$this->assertSame(
			array(
				Mother\BASE_URL . '/subscriptions/test',
				Mother\FALLBACK_BASE_URL . '/subscriptions/test',
			),
			$requested_urls
		);

	}

	function test_post_retries_with_fallback_url_after_dns_failure() {

		$requested_urls = array();
		$http_mock      = function( $preempt, $args, $url ) use ( &$requested_urls ) {
			$requested_urls[] = $url;

			if ( strpos( $url, Mother\BASE_URL . '/subscriptions/test' ) === 0 ) {
				return new WP_Error( 'http_request_failed', 'cURL error 6: Could not resolve host: api.jeero.ooo' );
			}

			if ( strpos( $url, Mother\FALLBACK_BASE_URL . '/subscriptions/test' ) === 0 ) {
				return array(
					'body'     => wp_json_encode( array( 'status' => 'ok' ) ),
					'response' => array(
						'code'    => 200,
						'message' => 'OK',
					),
				);
			}

			return $preempt;
		};
		add_filter( 'pre_http_request', $http_mock, 10, 3 );

		$result = Mother\post( 'subscriptions/test', array( 'enabled' => true ) );

		remove_filter( 'pre_http_request', $http_mock, 10 );

		$this->assertSame( array( 'status' => 'ok' ), $result );
		$this->assertSame(
			array(
				Mother\BASE_URL . '/subscriptions/test',
				Mother\FALLBACK_BASE_URL . '/subscriptions/test',
			),
			$requested_urls
		);

	}

}
