<?php
namespace Jeero\Admin\Logs;

function do_admin_page() {
	?><div class="wrap">
		<h1 class="wp-heading-inline"><?php _e( 'Jeero Logs', 'jeero' ); ?></h1>
		<a href="#" class="page-title-action"><?php _e( 'Download logfile', 'jeero' ); ?></a>
		<hr class="wp-header-end">
			
		<textarea class="large-text code" rows="20" readonly><?php
			echo \Jeero\Logs\get_log_file_content();
		?></textarea>
	</div><?php
}
