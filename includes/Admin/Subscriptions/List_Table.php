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
	 * @since	1.1		Removed Interval column.
	 * @return	array	The columns for the List Table.
	 */
	function get_columns() {
		$columns = array(
			'subscription' => __( 'Source', 'jeero' ),
			'calendar' => __( 'Destination', 'jeero' ),
			'next_sync' => __( 'Next sync', 'jeero' ),
			'plan' => __( 'Current plan', 'jeero' ),
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
	 * @return	void
	 */
    function column_limit( $subscription ) {

	    $limit = $subscription->get( 'limit');
	    
	    if ( empty( $limit ) ) {
		    return __( 'Unknown', 'jeero' );
	    }
	    
	    return sprintf( _n( '%d event', '%d events', $limit, 'jeero' ), $limit );
    }
    
	/**
	 * Outputs the content for the Next Sync column.
	 * 
	 * @since	1.0
	 * @return	void
	 */
    function column_next_sync( $subscription ) {
		
		$next_delivery = $subscription->get( 'next_delivery' ) + get_option( 'gmt_offset' ) * HOUR_IN_SECONDS;

	    if ( empty( $next_delivery ) ) {
		    return;
	    }
	    
	    ob_start();
	    
	    ?><span title="<?php
		    echo date_i18n( 'd-m-Y H:i:s', $next_delivery ); 
		?>"><?php 
		    echo human_time_diff( $next_delivery, current_time( 'timestamp' ) );
		?></span><?php
			
		return ob_get_clean();
	}
	
	function column_plan( $subscription ) {
		
		$plans = \Jeero\Mother\get_plans();
		
		ob_start();
		
		?><ul class="plans"><?php
			
			$current_plan = $subscription->get( 'plan' );
			
			foreach( $plans as $plan ) {
				?><li<?php if ( $plan[ 'name' ] == $current_plan[ 'name' ] ) { ?> class="current"<?php } ?>>
					<div class="limit"><?php printf( __( '%d events', 'jeero' ), $plan[ 'limit' ] );?></div>
					<div class="monthly_rate"><?php 
						if ( 0 == $plan[ 'rate_monthly' ] ) {
							_e( 'free', 'jeero' );
						} else {
							printf( '&euro; %d/mo', $plan[ 'rate_monthly' ] / 100 );
						}
					?></div>
				</li><?php
			}
			
		?></ul>
		
		<div class="row-actions">
			<span>
				<a href="">Change plan</a>
			</span>
		</div>
		<?php
		
		return ob_get_clean();
	}
    
    
	/**
	 * Outputs the content for the Subscription column.
	 * 
	 * @since	1.0
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

					echo $subscription->get( 'theater' )[ 'title' ];
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
	 * Outputs the content for a empty List Table.
	 * 
	 * @since	1.0
	 * @return	void
	 */
    function no_items() {
		?><div class="onboarding">
			<p><?php 
				_e( 'Jeero synchronizes your ticketing solution with your favourite calendar plugin.', 'jeero' );
			?></p>
			<p>
				<a href="<?php echo get_new_subscription_url(); ?>" class="button button-primary"><?php
					_e( 'Connect your ticketing solution', 'jeero' ); 
				?></a>
			</p>
		</div><?php
	}

	/**
	 * Loads all Subscriptions for the List Table.
	 * 
	 * @since	1.0
	 * @return 	void
	 */
	function prepare_items() {
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = array();
		$this->_column_headers = array($columns, $hidden, $sortable);
		
		$this->subscriptions = Subscriptions\get_subscriptions();

		if ( is_wp_error( $this->subscriptions ) ) {
			Admin\Notices\add_error( $this->subscriptions );
			$this->items = array();		
			return false;
		}
		
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