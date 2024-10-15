<?php
/**
 * Handles logging to the Jeero log file.
 *
 * @since	1.18
 *
 */
namespace Jeero\Logs;

const MAX_LOG_FILESIZE = 5242880; //Max filesize in bytes (e.q. 5MB)
const LOG_UID_KEY = 'jeero_log_uid_key';

/**
 * Attaches log import to inbox filter.
 * @since	1.21
 */
add_filter( 'jeero/inbox/process/item/log', __NAMESPACE__.'\log_from_inbox', 10, 5 );

/**
 * Creates the upload dir for the Jeero log file.
 * 
 * @since	1.18
 * @since	1.21	Improved error handling is creating logfile folder fails.
 *
 * @return	string	The path to the upload dir.
 */
function create_upload_dir() {

	$wp_upload_dir = \wp_upload_dir();
	$upload_dir_path = sprintf( '%s/jeero/logs/', $wp_upload_dir['basedir'] );

	if ( is_dir( $upload_dir_path ) ) {
		return $upload_dir_path;
	}
	
	if ( !wp_mkdir_p( $upload_dir_path ) ) {
		return new WP_Error( 'logs', sprintf( 'Unable to create %s folder for Jeero log', $upload_dir_path ) );
	}
	
	$index_php_path = $upload_dir_path.'index.php';
	$index_php_content = '<?php // Silence is golden';
	file_put_contents($index_php_path, $index_php_content );

	$htaccess_path = $upload_dir_path.'.htaccess';
	$htaccess_content = '<Files jeero.log>deny from all</Files>';
	file_put_contents($htaccess_path, $htaccess_content );
	
	return $upload_dir_path;
}

/**
 * Gets the path to the upload dir for the Jeero log file.
 * 
 * @since	1.18
 * @return	string	The path to the upload dir.
 */
function get_upload_dir() {

	return create_upload_dir();

}

/**
 * Gets the UUID used for the Jeero log filename.
 * 
 * @since	1.18
 * @return	string	The UUID used for the Jeero log filename.
 */
function get_uid( $slug ) {
	
	$log_uid_key = sprintf( '%s-%s', $slug, LOG_UID_KEY );
	
	$uid = get_option( $log_uid_key );
	
	if ( empty( $uid ) ) {
		
		$uid = \wp_generate_uuid4();
		update_option( $log_uid_key, $uid, false );
		
	}
	
	return $uid;
	
}

/**
 * Gets the path to the Jeero logfile.
 * 
 * @since	1.18
 * @since	1.21	Improved error handling is getting logfile folder fails.
 *
 * @return	string 	The path to the Jeero logfile.
 */
function get_file_path( $slug ) {

	$upload_dir = get_upload_dir();
	
	if ( \is_wp_error( $upload_dir ) ) {
		return $upload_dir;
	}
	
	return sprintf( '%s%s.log', $upload_dir, get_uid( $slug ) );
	
}

/**
 * Rotates the Jeero logfile.
 * 
 * @since	1.18
 * @since	1.21	Improved error handling if getting logfile path fails.
 *
 * @return	void
 */
function rotate_logs( $slug ) {
	
	$file_path = get_file_path( $slug );
	
	if ( \is_wp_error( $file_path ) ) {
		return;
	}
	
	if ( !file_exists( $file_path ) ) {
		return;
	}
	
	if ( filesize( $file_path ) < MAX_LOG_FILESIZE ) {
		return;
	}
	
	rename( $file_path, $file_path.'.1' );
	
}

/**
 * Logs a message to the Jeero logfile.
 * 
 * @since	1.18
 * @since	1.21	Improved error handling is getting logfile path fails.
 * @since	1.29	Fixed timestamp of logs, now uses local time.
 * @since	1.30	Check if logs are enabled before logging.
 *
 * @param 	string	$message
 * @return	void
 */
function log( $message, $slug = 'local' ) {
	
    if ( !\get_option( 'jeero/enable_logs' ) ) {
	    return;
	}
	
	rotate_logs( $slug );
	
	$file_path = get_file_path( $slug );
	
	if ( \is_wp_error( $file_path ) ) {
		return $file_path;
	}
	
	$timezone_string = get_option('timezone_string');
	if (empty( $timezone_string ) ) {
	    $gmt_offset = get_option( 'gmt_offset' );
	    $timezone_string = $gmt_offset ? timezone_name_from_abbr( '', $gmt_offset * 3600, 0 ) : 'UTC';
	}			

	$time = new \DateTime( );
	$time->setTimezone( new \DateTimeZone( $timezone_string ) );			

	$message = sprintf( "[%s] %s\n", $time->format( 'r' ), $message );
	
	error_log( $message, 3, $file_path );
	
}

/**
 * Logs a message from the inbox in the Jeero logfile.
 * 
 * @since	1.21
 */
function log_from_inbox( $result, $data, $raw, $theater, $subscription ) {
	
	if ( is_wp_error( $result ) ) {
		return $result;
	}
	
	$message = $data[ 'message' ];
	
	if ( !empty( $theater ) ) {
		$message = sprintf( '[%s] %s', $theater, $message );
	}
	
	log( $message, 'remote' );
	
	return $result;
	
}

/**
 * Gets the contents of the Jeero logfile.
 * 
 * @since	1.18
 * @since	1.21	Improved error handling is getting logfile path fails.
 *
 * @return	string
 */
function get_log_file_content( $slug ) {
	
	$file_path = get_file_path( $slug );
	
	if ( \is_wp_error( $file_path ) ) {
		return $file_path->get_error_message();
	}
	
	if ( !file_exists( $file_path ) ) {
		return '';
	}
	
	return file_get_contents( $file_path );
	
}

function get_logs() {
	
	$logs = array(
		'local' => __( 'Local Log', 'jeero' ),
		'remote' => __( 'Remote Log', 'jeero' ),		
	);
	
	return $logs;	
	
}