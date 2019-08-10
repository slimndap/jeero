<?php
namespace Jeero\Admin\Subscriptions;

use Jeero\Subscriptions;
use Jeero\Admin;

if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class List_Table extends \WP_List_Table {
	
	function get_columns() {
		$columns = array(
			'logo' => '',
			'subscription' => 'Source',
			'calendar' => 'Destination',
			'interval' => 'Interval',
			'next_delivery' => 'Next sync',
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
    
    function column_logo( $subscription ) {

		if ( empty( $subscription->get( 'logo' ) ) ) {
			return;
		}
		
		$settings = $subscription->get( 'settings' );
		
		ob_start();
		?><img src="<?php echo $subscription->get( 'logo' ); ?>" alt="<?php printf( __( '%s logo', 'jeero' ), $settings[ 'theater' ] ); ?>" style="width: 40px; height: auto;"><?php
		return ob_get_clean();

    }
    
    function column_interval( $subscription ) {
	    
	    $interval = $subscription->get( 'interval');
	    
	    if ( empty( $interval ) ) {
		    return;
	    }
	    
	    return human_time_diff( 0, $subscription->get( 'interval') );
    }
    
	function column_next_delivery( $subscription ) {
		
		$next_delivery = $subscription->get( 'next_delivery' );

	    if ( empty( $next_delivery ) ) {
		    return;
	    }
	    
	    return human_time_diff( time(), $subscription->get( 'next_delivery' ) );
		return date_i18n( 'd-m-Y H:i:s', $subscription->get( 'next_delivery' ) );
	}
    
    function column_subscription( $subscription ) {
	    
	    $actions = array(
		    'edit' => '<a href="'.get_admin_edit_url( $subscription->get( 'ID' ) ).'">'.__( 'Edit', 'jeero' ).'</a>',
	    );
	    
		$settings = $subscription->get( 'settings' );
		
		ob_start();
		
		?><strong>
			<a class="row-title" href="<?php echo get_admin_edit_url( $subscription->get( 'ID' ) ); ?>"><?php
				if ( !empty( $settings[ 'theater' ] ) ) {
					echo $settings[ 'theater' ];
				}
			?></a> - 
			<span><?php echo $subscription->get( 'status' ); ?></span>
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
			Admin/add_error( $subscriptions );
			$this->items = array();		
			return false;
		}
		
		$this->items = $subscriptions;		
	}
	
}