<?php
namespace Jeero\Admin\Subscriptions;

use Jeero\Subscriptions;

if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class List_Table extends \WP_List_Table {
	
	function get_columns() {
		$columns = array(
			'theater' => 'Title',
			'calendar'    => 'Author',
			'status'      => 'ISBN'
		);
		return $columns;
	}
	
	function no_items() {
		?><p><?php 
			_e( 'Jeero synchronizes your ticketing solution with your favourite calendar plugin.', 'jeero' );
		?></p>
		<p>
			<a href="<?php echo get_new_subscription_url(); ?>" class="button button-primary"><?php
				_e( 'Connect your ticketing solution', 'jeero' ); 
			?></a>
		</p><?php
	}

	function prepare_items() {
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = array();
		$this->_column_headers = array($columns, $hidden, $sortable);
		$this->items = Subscriptions\get_subscriptions();
	}
	
}