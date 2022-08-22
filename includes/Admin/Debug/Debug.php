<?php
namespace Jeero\Admin\Debug;

function do_admin_page() {
	?><div class="wrap">
		<h1 class="wp-heading-inline"><?php _e( 'Jeero Debug', 'jeero' ); ?></h1>
		<hr class="wp-header-end">
		
		<p>This page contains debug information about Jeero that can help us investigate any issues with your imports.<br>
			 Do not share this information with anyone, except when requested specifically by Jeero support.</p>
		
		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row">Logs</th>
					<td>
						<textarea class="large-text code" rows="20" readonly><?php
							echo \Jeero\Logs\get_log_file_content();
						?></textarea>
					</td>
				</tr>
				<tr>
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
