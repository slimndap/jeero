<?php
/**
 * Manages all of Jeero's Subscriptions.
 *
 */
namespace Jeero\Subscriptions;

use Jeero\Db;
use Jeero\Mother;
use Jeero\Calendars;
use Jeero\Theaters\Widgets;

const JEERO_SUBSCRIPTIONS_STATUS_SETUP = 'setup';
const JEERO_SUBSCRIPTIONS_STATUS_READY = 'ready';

/**
 * Adds a new Subscription.
 *
 * Asks Mother to setup a new Subscription.
 * 
 * @return	string	The ID of the new Subscription.
 */
function add_subscription( ) {
	
	$answer = Mother\subscribe_me();

	if ( is_wp_error( $answer ) ) {
		return $answer;
	}

	return $answer[ 'id' ];

}


/**
 * Gets the Settings of all Subscriptions.
 * 
 * @since	1.0
 * @return	array
 */
function get_setting_values() {

	$subscriptions = Db\Subscriptions\get_subscriptions();

	$settings = array();	
	foreach( $subscriptions as $subscription_id => $subscription_data ) {
		
		$subscription = new Subscription( $subscription_id );
		$settings[ $subscription_id ] = $subscription->get_settings();
	}
	
	return $settings;	
			
}
/**
 * Gets a Subscription.
 *
 * Gets the Subscription info from Mother and loads the settings from the DB.
 * 
 * @since	1.0
 * @since	1.4		Set a default value for 'interval'.
 * 					Added support for custom calendar fields.
 * @since	1.5		Removed calendar activation checkboxes.
 *					They are now managed on the corresponding calendar tabs.
 * @since	1.10	Set subscription fields after loading fields from the calendar.
 * @since	1.21	Improved error handling if getting subscriptions fails.
 *
 * @param 	int						$subscription_id	The Subscription ID.
 * @return	Subscription|WP_Error	The Subscription. Or an error if something went wrong.
 */
function get_subscription( $subscription_id ) {

	// Get the settings from the DB.
	$subscription = new \Jeero\Subscriptions\Subscription( $subscription_id );

	// Ask Mother for subscription info, based on the current settings.
	$answer = Mother\get_subscription( $subscription_id, $subscription->get_settings() );

	if ( \is_wp_error( $answer ) ) {
		return $answer;
	}
	
	$defaults = array(
		'status' => false,
		'logo' => false,
		'fields' => array(),
		'inactive' => false,
		'interval' => null,
		'next_delivery' => null,
		'theater' => array(),
		'limit' => null,
	);
	
	$answer = wp_parse_args( $answer, $defaults );
	
	// Add the subscription info to the Subscription.
	$subscription->set( 'status', $answer[ 'status' ] );
	$subscription->set( 'logo', $answer[ 'logo' ] );
	$subscription->set( 'inactive', $answer[ 'inactive' ] );
	$subscription->set( 'interval', $answer[ 'interval' ] );
	$subscription->set( 'next_delivery', $answer[ 'next_delivery' ] );
	$subscription->set( 'limit', $answer[ 'limit' ] );
	$subscription->set( 'theater', $answer[ 'theater' ] );

	$fields = array(
		array(
			'type' => 'Tab',
			'name' => 'generic',
			'label' => __( 'General', 'jeero' ),
		),
	);
		
	// Add fields from Mother.
	if ( !empty( $answer[ 'fields' ] ) ) {
		$fields = array_merge( $fields, $answer[ 'fields' ] );
	}
	
	// Add fields from calendars.
	foreach( Calendars\get_active_calendars() as $calendar ) {
		$fields = array_merge( $fields, $calendar->get_setting_fields( $subscription ) );			
	}

	$fields = array_merge( $fields, get_widget_fields( $subscription ) );

	$subscription->set( 'fields', $fields );

	return $subscription;
}

/**
 * Get read-only widget fields for a subscription's selected theater.
 *
 * @since 1.34
 *
 * @param Subscription $subscription The subscription.
 * @return array[]
 */
function get_widget_fields( Subscription $subscription ) {

	$widgets = Widgets\get_supported_widgets_for_subscription( $subscription );

	if ( empty( $widgets ) ) {
		return array();
	}

	$items = array();
	foreach ( $widgets as $widget_name ) {
		$items[] = sprintf(
			'<li>%s</li>',
			esc_html( Widgets\get_widget_label( $widget_name ) )
		);
	}

	return array(
		array(
			'type'  => 'Tab',
			'name'  => 'widgets',
			'label' => __( 'Widgets', 'jeero' ),
		),
		array(
			'type'  => 'Error',
			'name'  => 'widgets/list',
			'label' => sprintf(
				__( '%s support the following widgets', 'jeero' ),
				esc_html( get_theater_label( $subscription ) )
			),
			'value' => sprintf(
				'<ul class="jeero-widget-list">%s</ul>',
				implode( '', $items )
			),
		),
	);

}

/**
 * Get the display label for a subscription's theater.
 *
 * @since 1.34
 *
 * @param Subscription $subscription The subscription.
 * @return string
 */
function get_theater_label( Subscription $subscription ) {

	$theater = $subscription->get( 'theater' );

	if ( is_array( $theater ) ) {
		if ( ! empty( $theater['title'] ) && is_scalar( $theater['title'] ) ) {
			return (string) $theater['title'];
		}

		if ( ! empty( $theater['name'] ) && is_scalar( $theater['name'] ) ) {
			return (string) $theater['name'];
		}
	}

	$theater_name = $subscription->get_setting( 'theater' );

	if ( empty( $theater_name ) || ! is_scalar( $theater_name ) ) {
		return __( 'This theater', 'jeero' );
	}

	return (string) $theater_name;

}

/**
 * Gets all of Jeero's Subscriptions.
 * 
 * Gets the Subscriptions settings from the database and the info from Mother.
 * 
 * @return	Subscription[]	An array containing all of Jeero's Subscriptions.
 */
function get_subscriptions() {

	$settings = get_setting_values();

	// Ask Mother for a list of up-to-date subscriptions.
	$answers = Mother\get_subscriptions( $settings );
	
	if ( is_wp_error( $answers ) ) {
		return $answers;
	}

	if ( \is_wp_error(  $answers ) ) {
		return $answers;
	}

	// Update the Subscriptions in the DB.
	$subscriptions = array();
	foreach( $answers as $answer ) {
		
		$defaults = array(
			'status' => false,
			'logo' => false,
			'fields' => array(),
			'inactive' => null,
			'interval' => null,
			'next_delivery' => null,
			'limit' => null,
		);
		
		$answer = wp_parse_args( $answer, $defaults );

		$subscription = new Subscription( $answer[ 'id' ] );
		$subscription->set( 'status', $answer[ 'status' ] );
		$subscription->set( 'logo', $answer[ 'logo' ] );
		$subscription->set( 'fields', $answer[ 'fields' ] );
				
		$subscription->set( 'inactive', $answer[ 'inactive' ] );
		$subscription->set( 'interval', $answer[ 'interval' ] );
		$subscription->set( 'next_delivery', $answer[ 'next_delivery' ] );
		$subscription->set( 'limit', $answer[ 'limit' ] );
		
		if ( isset( $answer[ 'theater' ] ) ) {
			$subscription->set( 'theater', $answer[ 'theater' ] );
		}

		$subscriptions[ $subscription->get( 'ID' ) ] = $subscription;
	}
	
	return $subscriptions;	
		
}
