<?php
namespace Jeero\Admin\Debug;

add_action( 'admin_enqueue_scripts', __NAMESPACE__.'\enqueue_scripts', 9 );
add_filter( 'heartbeat_received', __NAMESPACE__.'\receive_heartbeat', 10, 2 );

/**
 * Enqueues templates scripts on the Jeero admin debug page.
 * 
 * @since	1.24
 * @return 	void
 */
function enqueue_scripts( ) {

	$current_screen = get_current_screen();
	
	if ( 'admin_page_jeero/debug' != $current_screen->id ) {
		return;
	}
	
	\wp_enqueue_script( 'jeero/templates', \Jeero\PLUGIN_URI . 'assets/js/debug.js', array( 'jquery' ), \Jeero\VERSION );

}

/**
 * Adds the Jeero debug log to the heartbeat response.
 * 
 * The debug admin screen uses the WordPress Heartbeat API to auto-update the Jeero debug log that is displayed in the screen.
 * 
 * @since	1.24
 * @return 	array	$response	The heartbeat response.
 */
function receive_heartbeat( array $response, array $data ) {

	if ( empty( $data[ 'jeero_heartbeat' ] ) ) {
		return $response;
	}
	
	if ( 'get_debug_log' != $data['jeero_heartbeat'] ) {
		return $response;		
	}
	
	if ( !current_user_can( 'manage_options' ) ) {
		$response[ 'jeero_debug_log_local' ] = __( 'Access to the Jeero debug log is denied.', 'jeero' );
	} else {
		$response[ 'jeero_debug_log_local' ] = \Jeero\Logs\get_log_file_content( 'local' );		
		$response[ 'jeero_debug_log_remote' ] = \Jeero\Logs\get_log_file_content( 'remote' );		
	}
	
	return $response;
	
}

function do_admin_page() {
	
	?><div class="wrap">
		<h1 class="wp-heading-inline"><?php _e( 'Jeero Debug', 'jeero' ); ?></h1>
		<hr class="wp-header-end">
		
		<p>This page contains debug information about Jeero that can help us investigate any issues with your imports.<br>
			 Do not share this information with anyone, except when requested specifically by Jeero support.</p>
		
		<table class="form-table" role="presentation">
			<tbody><?php
				
				$logs = array(
					'local' => __( 'Local Log', 'jeero' ),
					'remote' => __( 'Remote Log', 'jeero' ),		
				);
	
				foreach( $logs as $log_slug => $log_label ) {
					?><tr>
						<th scope="row"><?php echo esc_html( $log_label ); ?></th>
						<td>
							<textarea class="jeero_debug_log large-text code" rows="20" data-debug_log_slug="<?php echo esc_attr( $log_slug ); ?>" readonly><?php
								echo \Jeero\Logs\get_log_file_content( $log_slug );
							?></textarea>
						</td>
					</tr><?php
				}
				?><tr>
					<th scope="row">Settings</th>
					<td>
						<textarea class="large-text code" rows="20" readonly><?php
							$subscriptions = \Jeero\Db\Subscriptions\get_subscriptions();
							echo json_encode( $subscriptions );
						?></textarea>
						<p class="description"><?php
							_e( 'This information can potentially disclose the credentials of your ticketing solution. Only share this information  if you feel comfortable sharing this information with Jeero support.', 'jeero' );
						?></p>
					</td>
				</tr>
			</tbody>
		</table>
			
	</div><?php
}
