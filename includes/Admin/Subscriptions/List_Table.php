<?php
/**	
 * Manages the List Table on the Subscriptions Admin page.
 *
 * @since 1.0
 */
namespace Jeero\Admin\Subscriptions;

use Jeero\Subscriptions;
use Jeero\Admin;
use Jeero\Calendars;

if( ! class_exists( '\WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * List_Table class.
 */
class List_Table extends \WP_List_Table {
	
	public $subscriptions;
	
	function get_views( ) {

		$views = array();
		
		if ( empty( $this->subscriptions ) ) {
			return $views;
		}
		
		$counter = array(
			'active' => 0,
			'inactive' => 0,
		);
		
		foreach( $this->subscriptions as $subscription ) {
			
			if ( $subscription->get( 'inactive' ) ) {
				$counter[ 'inactive' ]++;
			} else {
				$counter[ 'active' ]++;
			}

		}
		
		if ( $counter[ 'inactive' ] > 0 ) {
			
			if ( empty( $_GET[ 'inactive' ] ) ) {
				$views = array(
					'active' => '<a href="'.get_admin_page_url().'" class="current">'.__( 'Active imports', 'jeero' ).' <span class="count">('.$counter[ 'active' ]	.')</span></a>',
					'inactive' => '<a href="'.\add_query_arg( 'inactive', 1, get_admin_page_url() ).'">'.__( 'Inactive imports', 'jeero' ).' <span class="count">('.$counter[ 'inactive' ]	.')</span></a>',
				);				
			} else {
				$views = array(
					'active' => '<a href="'.get_admin_page_url().'">'.__( 'Active imports', 'jeero' ).' <span class="count">('.$counter[ 'active' ]	.')</span></a>',
					'inactive' => '<a href="'.\add_query_arg( 'inactive', 1, get_admin_page_url() ).'" class="current">'.__( 'Inactive imports', 'jeero' ).' <span class="count">('.$counter[ 'inactive' ]	.')</span></a>',
				);				
			}
		}
		
		return $views;
	}
	
	/**
	 * Gets the columns for the List Table.
	 * 
	 * @since	1.0
	 * @since	1.1		Removed interval column.
	 * @since	1.7		Removed next_sync column. 
	 *					Renamed subscription and calendar columns.
	 * @return	array	The columns for the List Table.
	 */
	function get_columns() {
		$columns = array(
			'subscription' => __( 'Ticketing solution', 'jeero' ),
			'calendar' => __( 'Calendar plugin', 'jeero' ),
			'limit' => __( 'Limit', 'jeero' ),
		);
		return $columns;
	}
	
	/**
	 * Outputs the content for the Calendar column.
	 * 
	 * @since	1.0
	 * @return	void
	 */
    function column_calendar( $subscription ) {

		$settings = $subscription->get( 'settings' );
		
		if ( empty( $settings[ 'calendar' ] ) ) {
			?>&mdash;<?php
			return;
		}
		
		foreach( $settings[ 'calendar' ] as $slug ) {
			
			$calendar = Calendars\get_calendar( $slug );
			?><div><?php echo $calendar->get( 'name' ); ?></div><?php
		}

    }
    
	/**
	 * Outputs the content for the Events Limit column.
	 * 
	 * @since	1.0
	 * @since	1.21		Added an upgrade link for imports that are limited to less than 500 events.
	 * @return	string
	 */
    function column_limit( $subscription ) {

	    $limit = $subscription->get( 'limit');
	    
	    if ( empty( $limit ) ) {
		    return __( 'Unknown', 'jeero' );
	    }
	    
	    ob_start();
	    
	    printf( _n( '%d event', '%d events', $limit, 'jeero' ), $limit );
	    
	    if ( 500 > $limit ) {
		    
		    $upgrade_url = add_query_arg( 'subscription', $subscription->ID, 'https://jeero.ooo/product/upgrade-jeero/' );
			?><br/>
			<a href="<?php echo $upgrade_url; ?>" target="_blank"><?php
				_e( 'Upgrade', 'jeero' );
			?></a><?php
	    }
	    
	    return ob_get_clean();
    }
        
	/**
	 * Outputs the content for the Subscription column.
	 * 
	 * @since	1.0
	 * @since	1.7	Show '-' if subscription doesn't have a theater.
	 * @return	void
	 */
    function column_subscription( $subscription ) {
	    
	    $actions = array(
		    'edit' => '<a href="'.get_admin_edit_url( $subscription->get( 'ID' ) ).'">'.__( 'Edit', 'jeero' ).'</a>',
	    );
	    
	    if ( $subscription->get( 'inactive' ) ) {
		    $url = \wp_nonce_url( get_admin_page_url(), 'activate', 'jeero/nonce' );
		    $url = \add_query_arg( 'subscription_id', $subscription->get( 'ID' ), $url );
		    $actions[ 'activate' ] = '<a href="'.$url.'">'.__( 'Activate', 'jeero' ).'</a>';
	    } else {
		    $url = \wp_nonce_url( get_admin_page_url(), 'deactivate', 'jeero/nonce' );
		    $url = \add_query_arg( 'subscription_id', $subscription->get( 'ID' ), $url );
		    $actions[ 'deactivate' ] = '<a href="'.$url.'">'.__( 'Deactivate', 'jeero' ).'</a>';		    
	    }
	    
		$settings = $subscription->get( 'settings' );
		
		ob_start();
		
		?><strong>
			<a class="row-title" href="<?php echo get_admin_edit_url( $subscription->get( 'ID' ) ); ?>"><?php

				if ( !empty( $subscription->get( 'theater' ) ) ) {
					
					if ( !empty( $subscription->get( 'theater' )[ 'logo' ] ) ) {
						?><img src="<?php echo $subscription->get( 'theater' )[ 'logo' ]; ?>" alt="<?php printf( __( '%s logo', 'jeero' ), $settings[ 'theater' ] ); ?>" style="max-height: 1.5em; max-width: 1.5em; height: auto; width: auto; margin-right: 0.5em;"><?php						
					}

					if ( 'theater' == $subscription->get( 'theater' )[ 'name' ] ) {
						?>&mdash;<?php
					} else {
						echo $subscription->get( 'theater' )[ 'title' ];
					}
				} elseif ( !empty( $settings[ 'theater' ] ) ) {
					echo ucwords( $settings[ 'theater' ] );
				} else {
					?>&mdash;<?php
				}
			?></a>
		</strong><?php
			
		echo $this->row_actions( $actions );
		
		return ob_get_clean();
	    
    }
    
	/**
	 * Outputs the content for an empty List Table.
	 * 
	 * @since	1.0
	 * @since	1.7	Removed onboarding.
	 * @return	void
	 */
    function no_items() {
	    
	    if ( empty( $this->subscriptions ) ) {
			_e( 'No imports found.', 'jeero' );
			return;
		}

		$inactive = !empty( $_GET[ 'inactive' ] );
		if ( $inactive ) {
			_e( 'No inactive imports found.', 'jeero' );
			return;
		}
		
		_e( 'No active imports found.', 'jeero' );

		
	}

	/**
	 * Loads all Subscriptions for the List Table.
	 * 
	 * @since	1.0
	 * @since	1.21		Improved handling of errors if loading of subscriptions fails.
	 * @return 	void
	 */
	function prepare_items() {
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = array();
		$this->_column_headers = array($columns, $hidden, $sortable);
		
		$subscriptions = Subscriptions\get_subscriptions();

		if ( is_wp_error( $subscriptions ) ) {
			Admin\Notices\add_error( $subscriptions );
			$this->items = array();		
			$subscriptions = array();
		}
		
		$this->subscriptions = $subscriptions;
		
		$filtered_subscriptions = array();
		
		$inactive = !empty( $_GET[ 'inactive' ] );
		foreach( $this->subscriptions as $subscription ) {
			if ( $subscription->get( 'inactive' ) == $inactive ) {
				$filtered_subscriptions[] = $subscription;
			}
		}
		
		$this->items = $filtered_subscriptions;		
	}
	
}