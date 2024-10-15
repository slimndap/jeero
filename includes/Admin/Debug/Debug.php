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

	if ( 'jeero_page_jeero/debug' != $current_screen->id ) {
		return;
	}

	\wp_register_script( 'chart', 'https://cdn.jsdelivr.net/npm/chart.js@^3', array(), null, true );	
	\wp_enqueue_script( 
		'jeero/debug', 
		\Jeero\PLUGIN_URI . 'assets/js/debug.js', 
		array( 'jquery', 'chart', 'wp-theme-plugin-editor' ), 
		\Jeero\VERSION 
	);
	\wp_localize_script( 'jeero/debug', 'jeero_debug_logs', \Jeero\Logs\get_logs() );

	$jeero_debug = array(
		'stats' => \Jeero\Logs\Stats\get_stats(),
		'settings' => array(
			'codeEditor' =>	\wp_enqueue_code_editor(
				array(
					'type' => 'application/json',
					'viewportMargin' => 'Infinity',
				)
			),
		),		
	);

	\wp_localize_script( 'jeero/debug', 'jeero_debug', $jeero_debug );
	
	\wp_enqueue_style( 'wp-codemirror' );

}

/**
 * Adds the Jeero debug log to the heartbeat response.
 * 
 * The debug admin screen uses the WordPress Heartbeat API to auto-update the Jeero debug log and stats that are displayed in the screen.
 * 
 * @since	1.24
 * @since	1.30	Added stats to heartbeat response.
 * @return 	array	$response	The heartbeat response.
 */
function receive_heartbeat( array $response, array $data ) {

	if ( empty( $data[ 'jeero_heartbeat' ] ) ) {
		return $response;
	}
	
	if ( 'get_debug_log' != $data['jeero_heartbeat'] ) {
		return $response;		
	}
	
	foreach( \Jeero\Logs\get_logs() as $log_slug => $log_label ) {
		
		
		if ( !current_user_can( 'manage_options' ) ) {
			$log_response = sprintf( __( 'Access to the Jeero %s is denied.', 'jeero' ), $log_label );
		} else {
			$log_response = \Jeero\Logs\get_log_file_content( $log_slug );
		}
		
		$response[ sprintf( 'jeero_debug_log_%s', $log_slug ) ] = $log_response;
	}
	
	$response[ 'jeero_debug_stats' ] = \Jeero\Logs\Stats\get_stats();
	
	return $response;
	
}

/**
 * Output the Jeero debug admin page.
 * 
 * @since	1.24
 * @since	1.30	Added stats.
 * @return 	void
 */
function do_admin_page() {
	
	?><div class="wrap">
		<h1 class="wp-heading-inline"><?php _e( 'Jeero Debug', 'jeero' ); ?></h1>
		<hr class="wp-header-end">
		
		<p><?php
			_e( 'This page contains debug information about Jeero that can help us investigate any issues with your imports.', 'jeero' );
			?><br><?php
			_e( 'Do not share this information with anyone, except when requested specifically by Jeero support.', 'jeero' ); ?></p>
		
		<table class="form-table" role="presentation" id="jeero_debug_content">
			<tbody><?php
				
				foreach( \Jeero\Logs\get_logs() as $log_slug => $log_label ) {
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
					<th scope="row"><?php _e( 'Stats', 'jeero' ); ?></th>
					<td><canvas id="jeero_debug_stats"></canvas></td>
				</tr>
				<tr>
					<th scope="row"><?php _e( 'Settings' ); ?></th>
					<td>
						<textarea id="jeero_debug_settings" class="large-text code" rows="20" readonly><?php
							$subscriptions = \Jeero\Db\Subscriptions\get_subscriptions();
							echo json_encode( $subscriptions, JSON_PRETTY_PRINT );
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
