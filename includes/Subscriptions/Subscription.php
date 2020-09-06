<?php
namespace Jeero\Subscriptions;

use Jeero\Db;
use Jeero\Admin;
use Jeero\Mother;

/**
 * Subscription class.
 * 
 */
class Subscription {
	
	/**
	 * The ID of this Subscription.
	 * @var		string 	$ID
	 * @since	1.0
	 */
	public $ID;
	
	/**
	 * The fields of this Subscription.
	 * The fields are provided by Mother, based on the value of $settings.
	 * @var		array	$fields
	 * @since	1.0
	 */
	public $fields = array();
	
	/**
	 * The maximum number of events that will be imported.
	 * 
	 * @var		int	$limit
	 * @since	1.0
	 */
	public $limit;
	
	public $inactive;
	
	/**
	 * The number of seconds between two imports.
	 * 
	 * @var		int	$interval
	 * @since	1.0
	 */
	public $interval;
	
	/**
	 * Time (UTC) after which there will be an update for this Subscription.
	 * So no need to check for updates before this time.
	 * 
	 * @var	int
	 */
	public $next_delivery;
	
	/**
	 * The Theater of this Subscription.
	 * 
	 * @var 	array
	 * @since 	1.0
	 */
	public $theater = array();
	
	/**
	 * The settings of this Subscription.
	 * @var array	$settings
	 * @since	1.0
	 */
	public $settings = array();
	
	/**
	 * The status of this Subscription.
	 * @var 	string	$status
	 * @since	1.0
	 */	
	public $status;
	
	/**
	 * Inits the Subscriptions. Sets the ID and load the settings from te DB.
	 * 
	 * @access public
	 * @param mixed $ID
	 * @return void
	 */
	function __construct( $ID ) {
		
		$this->set( 'ID', $ID );
		$this->load();
		
	}

	/**
	 * Gets a property from the Subscription.
	 * 
	 * @since	1.0
	 * @param 	string	$key
	 * @return	mixed
	 */
	function get( $key ) {
		
		if ( !isset( $this->{ $key } ) ) {
			return null;
		}
		
		return $this->{ $key };
		
	}
	
	/**
	 * Gets all fields of this Subscription.
	 * 
	 * @since	1.0
	 * @return 	Field[]		All fields of this Subscription.
	 */
	function get_fields() {
		
		$fields = array();

		foreach( $this->fields as $config ) {
			$setting = null;

			if ( $setting = $this->get_setting( $config[ 'name' ] ) ) {
				$fields[] = Fields\get_field_from_config( $config, $this->ID, $setting );
			} else {
				$fields[] = Fields\get_field_from_config( $config, $this->ID );				
			}
		}
		
		return $fields;		
		
	}
		
	/**
	 * Gets a setting of this Subscription.
	 * 
	 * @since	1.0
	 * @return 	mixed
	 */
	function get_setting( $name ) {
		
		$settings = $this->get( 'settings' );
		
		if ( isset( $settings[ $name ] ) ) {
			return $settings[ $name ];
		}
		
		return false;

	}
	
	function activate() {
		
		$answer = Mother\activate_subscription( $this->ID );
	
		if ( is_wp_error( $answer ) ) {
			return $answer;
		}
	
		return false == $answer[ 'inactive' ];
		
	}
	
	function deactivate() {

		$answer = Mother\deactivate_subscription( $this->ID );
	
		if ( is_wp_error( $answer ) ) {
			return $answer;
		}
	
		return true == $answer[ 'inactive' ];
		
	}
	
	/**
	 * Loads the Settings of this Subscription.
	 * 
	 * @since	1.0
	 * @return 	void
	 */
	function load( ) {
		
		$data = Db\Subscriptions\get_subscription( $this->ID );
		
		if ( is_null( $data ) ) {
			return;
		}
		
		$defaults = array(
			'theater' => false,
		);
		
		$settings = wp_parse_args( $data[ 'settings' ], $defaults );
		
		$this->settings = $settings;

	}

	/**
	 * Sets a property from the Subscription.
	 * 
	 * @since	1.0
	 * @param 	string	$key
	 * @param 	mixed	$value
	 * @return	void
	 */
	function set( $key, $value ) {
		
		$this->{ $key } = $value;
		
	}
	
	
	/**
	 * Save this Subscription to the DB. 
	 * 
	 * @since	1.0
	 * @return 	void
	 */
	function save() {
		
		Db\Subscriptions\save_subscription( $this->ID, $this->settings );
		
	}
	

}