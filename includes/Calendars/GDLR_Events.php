<?php
namespace Jeero\Calendars;

// Register new calendar.
register_calendar( __NAMESPACE__.'\\GDLR_Events' );

/**
 * GDLR_Events class.
 *
 * Adds support for the Goodlayers Event Post Type plugin. 
 *
 * @since	1.3
 * 
 * @extends Calendar
 */
class GDLR_Events extends Post_Based_Calendar {

	function __construct() {
		
		$this->slug = 'GDLR_Events';
		$this->name = __( 'Goodlayers Event Post Type', 'jeero' );
		
		parent::__construct();
		
	}
	
	function get_post_type() {
		
		global $theme_option;
	
		if ( empty( $theme_option[ 'event-slug' ] ) ) {
			return 'event';
		}
		
		return $theme_option[ 'event-slug' ];

	}
	
	/**
	 * Checks if this calendar is active.
	 * 
	 * @since	1.15
	 * @return	bool
	 */
	function is_active() {
		return function_exists( '\gdlr_event_init' );
	}
	
	function get_categories_taxonomy( $subscription ) {

		global $theme_option;
	
		if ( empty( $theme_option[ 'event-category-slug' ] ) ) {
			return 'event_category';
		}
		
		return $theme_option[ 'event-category-slug' ];
		
	}
	
	function get_post_option( $event_id ) {

		$post_option = \get_post_meta( $event_id, 'post-option', true );
		if ( empty( $post_option ) )  {
			return array();
		}
		return json_decode( $post_option, true ); 
		
	}
	
	/**
	 * Processes the data of an event coming from the Jeero inbox.
	 * 
	 * @since	1.3
	 * @since	1.3.3	No longer try to localize the event start time since the inbox already returns 
	 *					a local start time.
	 * @since	1.4		Added the subscription param.
	 * @since	1.5		Fixed a PHP warning for prices without a title.
	 *
	 * @param	mixed			$result		The result of any previous processing of this event.
	 * @param	array			$data		The structured data of the event.
	 * @param 	mixed			$raw		The raw data of the event coming from the Theater.
	 * @param 	string			$theater 	The theater.
	 * @param	Subscription		$theater		The subscription.
	 * @return	WP_Error|int					The post ID of the event or an error if someting went wrong.
	 */
	function process_data( $result, $data, $raw, $theater, $subscription ) {
		
		$result = parent::process_data( $result, $data, $raw, $theater, $subscription );
		
		if ( \is_wp_error( $result ) ) {			
			return $result;
		}
		
		$ref = $this->get_post_ref( $data );


		if ( $event_id = $this->get_event_by_ref( $ref, $theater ) ) {

			$event_start = strtotime( $data[ 'start' ] );

			$post_option = $this->get_post_option( $event_id );
			
			$post_option[ 'page-title' ] = $data[ 'production' ][ 'title' ];
			$post_option[ 'date' ] = date( 'Y-m-d', $event_start );
			$post_option[ 'time' ] = date( 'H:i', $event_start );
			$post_option[ 'buy-now' ] = $data[ 'tickets_url' ];
			$post_option[ 'location' ] = $data[ 'venue' ][ 'title' ];
			
			// Set status
			if ( ! empty( $data[ 'status' ] ) ) {
				
				switch ( $data[ 'status' ] ) {
					
					case 'cancelled':
						$status = 'cancelled';
						break;
						
					case 'soldout':
						$status = 'sold-out';
						break;
						
					default:
						$status = 'buy-now';
					
				}
			
				$post_option[ 'status' ] = $status;
				
			}
			
			// Set prices
			if ( ! empty( $data[ 'prices' ] ) ) {
				$prices = array();
				
				foreach( $data[ 'prices' ] as $price ) {
					if ( empty( $price[ 'title' ] ) ) {
						$prices[] = \number_format_i18n( $price[ 'amount' ], 2 );				
					} else {
						$prices[] = $price[ 'title' ].' '.\number_format_i18n( $price[ 'amount' ], 2 );									
					}
				}
				
				$post_option[ 'number' ] = implode( '<br>', $prices );
			}			
			
			\update_post_meta( $event_id, 'post-option', json_encode( $post_option ) );
			\update_post_meta( $event_id, 'gdlr-event-date', date( 'Y-m-d H:i', $event_start ) );

		}
		
		return $event_id;		
		
	}
	
}
