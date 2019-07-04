<?php
namespace Jeero\Subscriptions;

use Jeero\Db;

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
	
	public $fields = array();
	
	/**
	 * Time (UTC) after which there will be an update for this Subscription.
	 * So no need to check for updates before this time.
	 * 
	 * @var	int
	 */
	public $next_update;
	
	/**
	 * The settings of this Subscription.
	 * @var array	$settings
	 * @since	1.0
	 */
	public $settings = array();
	
	/**
	 * The status of this Subscription.
	 * @var string	$status
	 * @since	1.0
	 */	
	public $status;
	
	function __construct( $ID ) {
		
		$this->set( 'ID', $ID );
		$this->load();
		
	}

	function get( $key ) {
		
		if ( !isset( $this->{ $key } ) ) {
			return null;
		}
		
		return $this->{ $key };
		
	}
	
	function load( ) {
		
		$data = Db\Subscriptions\get_subscription( $this->ID );
		
		if ( is_null( $data ) ) {
			return;
		}
		
		$this->fields = $data[ 'fields' ];
		$this->settings = $data[ 'settings' ];
		$this->status = $data[ 'status' ];
		
	}

	function set( $key, $value ) {
		
		$this->{ $key } = $value;
		
	}
	
	function save() {
		
		$data = array(
			'fields' => $this->fields,
			'settings' => $this->settings,
			'status' => $this->status,	
		);

		Db\Subscriptions\save_subscription( $this->ID, $data );
		
	}

}