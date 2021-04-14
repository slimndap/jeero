<?php
namespace Jeero\Subscriptions\Fields;

use Jeero\Account;
use Jeero\Admin\Subscriptions;

class Plan extends Field {
		
	function get_control_html() {
		
		ob_start();
		
		$theater = $this->subscription->get( 'theater' );
		$limit = $this->subscription->get( 'limit' );
		
		?><p><em><?php
			printf( 
				__( 'Jeero currently imports up to %d upcoming %s events.' , 'jeero' ), 
				$limit,
				$theater[ 'title' ]
			);
		?></em></p>
		
		<p><?php
			_e( 'To change this number you can switch to one of the plans below:', 'jeero' );
		?></p>
		
		<table>
			<thead>
				<tr>
					<th><?php _e( 'Import', 'jeero' ); ?></th>
					<th><?php _e( 'Per month', 'jeero' ); ?></th>
					<th><?php _e( 'Per year', 'jeero' ); ?></th>
				</tr>
			</thead><?php
			foreach ( Account\get_plans() as $plan ) {
				$url = \Jeero\Admin\Subscriptions\get_admin_edit_url( $this->subscription->ID );
				$url = add_query_arg( 'limit', $plan[ 'limit' ], $url );
				$url = wp_nonce_url( $url, 'plan', 'jeero/nonce' );
				?><tr>
					<td><?php printf( __( 'up to %d events', 'jeero' ), $plan[ 'limit' ] ); ?></td>
					<td>
						<a href="<?php
							echo Account\get_add_plan_url( $this->subscription->ID, $plan[ 'limit' ], 'monthly' );
						?>" class="button">&euro; <?php echo $plan[ 'monthly' ]; ?>/<?php _e( 'month', 'jeero' );?></a></td>
					<td>
						<a href="<?php
							echo Account\get_add_plan_url( $this->subscription->ID, $plan[ 'limit' ], 'annually' );
						?>" class="button">&euro; <?php echo $plan[ 'annually' ]; ?>/<?php _e( 'year', 'jeero' );?></a></td>
				</tr><?php				
			}
		?></table><?php
			
		return ob_get_clean();
		
		
	}
	
}