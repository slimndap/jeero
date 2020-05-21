<?php
namespace Jeero\Admin\Subscriptions;

use Jeero\Subscriptions;
use Jeero\Admin;
use Jeero\Calendars;

if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class List_Table extends \WP_List_Table {
	
	function get_columns() {
		$columns = array(
			'subscription' => 'Source',
			'calendar' => 'Destination',
			'interval' => 'Interval',
			'next_delivery' => 'Next sync',
			'limit' => 'Limit',
		);
		return $columns;
	}
	
	function column_default( $item, $column_name ) {
        switch( $column_name ) {
            case 'calendar':
                return $item->get( $column_name );
            default:
                return print_r( $item, true ) ;
        }
    }
    
    function column_calendar( $subscription ) {

		$settings = $subscription->get( 'settings' );
		
		if ( empty( $settings[ 'calendar' ] ) ) {
			return;
		}
		
		foreach( $settings[ 'calendar' ] as $slug ) {
			
			$calendar = Calendars\get_calendar( $slug );
			?><div><?php echo $calendar->get( 'name' ); ?></div><?php
		}

    }
    
    function column_interval( $subscription ) {
	    
	    $interval = $subscription->get( 'interval');
	    
	    if ( empty( $interval ) ) {
		    return;
	    }
	    
	    return sprintf( __( 'Every %s', 'jeero' ), human_time_diff( 0, $subscription->get( 'interval') ) );
    }
    
    function column_limit( $subscription ) {

	    $limit = $subscription->get( 'limit');
	    
	    if ( empty( $limit ) ) {
		    return __( 'Unknown', 'jeero' );
	    }
	    
	    return sprintf( _n( '%d event', '%d events', $limit, 'jeero' ), $limit );
    }
    
    function column_next_delivery( $subscription ) {
		
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
    
    function column_subscription( $subscription ) {
	    
	    $actions = array(
		    'edit' => '<a href="'.get_admin_edit_url( $subscription->get( 'ID' ) ).'">'.__( 'Edit', 'jeero' ).'</a>',
	    );
	    
		$settings = $subscription->get( 'settings' );
		
		ob_start();
		
		?><strong>
			<a class="row-title" href="<?php echo get_admin_edit_url( $subscription->get( 'ID' ) ); ?>"><?php
				
				if ( !empty( $subscription->get( 'logo' ) ) ) {
					?><img src="<?php echo $subscription->get( 'logo' ); ?>" alt="<?php printf( __( '%s logo', 'jeero' ), $settings[ 'theater' ] ); ?>" style="width: auto; height: 1.5em;"> <?php
				}


				if ( !empty( $settings[ 'theater' ] ) ) {
					echo $settings[ 'theater' ];
				}
			?></a>
		</strong><?php
			
		echo $this->row_actions( $actions );
		
		return ob_get_clean();
	    
    }
    
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

	function prepare_items() {
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = array();
		$this->_column_headers = array($columns, $hidden, $sortable);
		
		$subscriptions = Subscriptions\get_subscriptions();

		if ( is_wp_error( $subscriptions ) ) {
			Admin\Notices\add_error( $subscriptions );
			$this->items = array();		
			return false;
		}
		
		$this->items = $subscriptions;		
	}
	
}