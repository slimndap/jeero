<?php
class Jeero_Test extends WP_UnitTestCase {
	
	function get_mock_response_for_add_subscription( $response, $endpoint, $args ) {
		
		$body = array(
			'id' => 'a fake ID',
			'status' => 'setup',
			'fields' => array(
				array(
					'name' => 'theater',
					'type' => 'select',
					'label' => 'Theater',
					'required' => true,
					'choices' => array(
						'veezi' => 'Veezi',
						'seatgeek' => 'Seatgeek',
						'stager' => 'Stager',	
					),
				),
			),
		);
		
		return array(
			'body' => json_encode( $body ),
			'response' => array(
				'code' => 200,
				'message' => 'OK',	
			),
		);
			
	}

	function get_mock_response_for_get_subscriptions( $response, $endpoint, $args ) {
		
		$body = array(
			array(
				'id' => 'a fake ID',
				'status' => 'setup',
				'fields' => array(
					array(
						'name' => 'theater',
						'type' => 'select',
						'label' => 'Theater',
						'required' => true,
						'choices' => array(
							'veezi' => 'Veezi',
							'seatgeek' => 'Seatgeek',
							'stager' => 'Stager',	
						),
					),
				),
			),			
			array(
				'id' => 'another fake ID',
				'status' => 'active',
				'next_update' => time() + DAY_IN_SECONDS,
				'interval' => 3600,
				'fields' => array(
					array(
						'name' => 'theater',
						'type' => 'select',
						'label' => 'Theater',
						'required' => true,
						'choices' => array(
							'veezi' => 'Veezi',
							'seatgeek' => 'Seatgeek',
							'stager' => 'Stager',	
						),
					),
				),
			),
		);
		
		return array(
			'body' => json_encode( $body ),
			'response' => array(
				'code' => 200,
				'message' => 'OK',	
			),
		);
			
	}
	
	/**
	 * Returns a mock response with an empty array.
	 * 
	 * @since	1.7
	 *
	 * @param 	array	$response
	 * @param 	string	$endpoint
	 * @param 	array	$args
	 * @return	array
	 */
	function get_mock_response_empty_array( $response, $endpoint, $args ) {
		
		return array(
			'body' => '[]',
			'response' => array(
				'code' => 200,
				'message' => 'OK',	
			),
		);
			
	}
	
	/**
	 * Returns a mock response for the subscriptions endpoint of Mother.
	 * 
	 * @since	1.?
	 * @since	1.4	Added 'theater' to the response.
	 *
	 * @param 	array	$response
	 * @param 	string	$endpoint
	 * @param 	array	$args
	 * @return	array
	 */
	function get_mock_response_for_get_subscription( $response, $endpoint, $args ) {
		
		if ( strpos( $endpoint, 'subscriptions/' !== 0 ) ) {
			return $response;
		}
		
		$body = array(
			'id' => 'a fake ID',
			'status' => 'setup',
			'fields' => array(
				array(
					'name' => 'theater',
					'type' => 'select',
					'label' => 'Theater',
					'required' => true,
					'choices' => array(
						'veezi' => 'Veezi',
						'seatgeek' => 'Seatgeek',
						'stager' => 'Stager',	
					),
				),
				array(
					'name' => 'test_field',
					'type' => 'text',
					'label' => 'Test field',
				)
			),
			'theater' => array(
				'name' => 'veezi',
				'title' => 'Veezi',				
			),
		);	
		
		return array(
			'body' => json_encode( $body ),
			'response' => array(
				'code' => 200,
				'message' => 'OK',	
			),
		);
			
	}
	
	function get_mock_response_for_get_inbox( $response, $endpoint, $args ) {
		
		$body = array(
			array(
				'ID' => 'a fake inbox ID',
				'action' => 'import',
				'item' => 'event',
				'theater' => 'veezi',
				'subscription_id' => 'a fake ID',
				'data' => array(
					'production' => array(
						'title' => 'A test event',
						'description' => 'A description.',
						'categories' => array(
							'Category A',
							'Category B',	
						),			
					),
					'start' => time() + 48 * HOUR_IN_SECONDS,
					'end' => time() + 90 * MINUTE_IN_SECONDS + 48 * HOUR_IN_SECONDS,
					'image' => '',
					'ref' => '123',
					'tickets_url' => 'https://slimndap.com',
					'venue' => array(
						'title' => 'Paard',
						'address' => array(
							
						),
					),
					'prices' => array(
						array(
							'title' => 'Regular',
							'amount' => '20',
							'currency' => 'EUR',
						),
					),	
				
				),
				'raw' => 'Raw event data',
			),
		);
		
		return array(
			'body' => json_encode( $body ),
			'response' => array(
				'code' => 200,
				'message' => 'OK',	
			),
		);
			
	}
}