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
 * Creates the upload dir for the Jeero log file.
 * 
 * @since	1.18
 * @return	string	The path to the upload dir.
 */
function create_upload_dir() {

	$wp_upload_dir = \wp_upload_dir();
	$upload_dir_path sprintf( '%s/jeero/logs/', $wp_upload_dir['basedir'] );

	if ( is_dir( $upload_dir_path ) ) {
		return $upload_dir_path;
	}
	
	mkdir( $upload_dir_path, 0700, true );
	
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
function get_uid() {
	
	$uid = get_option( LOG_UID_KEY );
	
	if ( empty( $uid ) ) {
		
		$uid = \wp_generate_uuid4();
		update_option( LOG_UID_KEY, $uid, false );
		
	}
	
	return $uid;
	
}

/**
 * Gets the path to the Jeero logfile.
 * 
 * @since	1.18
 * @return	string 	The path to the Jeero logfile.
 */
function get_file_path() {

	$upload_dir = get_upload_dir();
	return sprintf( '%s%s.log', $upload_dir, get_uid() );
	
}

/**
 * Rotates the Jeero logfile.
 * 
 * @since	1.18
 * @return	void
 */
function rotate_logs() {
	
	$file_path = get_file_path();
	
	if ( !file_exists( $file_path ) ) {
		return;
	}
	
	if ( filesize( $file_path ) < MAX_LOG_FILESIZE ) {
		return;
	}
	
	rename( $file_path, $file_path.'.1' );
	
}

/**
 * Logs a message in the Jeero logfile.
 * 
 * @since	1.18
 * @param 	string	$message
 * @return	void
 */
function log( $message ) {
	
	rotate_logs();
	
	$file_path = get_file_path();
	
	$message = sprintf( "[%s] %s\n", date( 'r' ), $message );
	
	error_log( $message, 3, $file_path );
	
}

/**
 * Gets the contents of the Jeero logfile.
 * 
 * @since	1.18
 * @return	string
 */
function get_log_file_content() {
	
	$file_path = get_file_path();
	
	if ( !file_exists( $file_path ) ) {
		return '';
	}
	
	return file_get_contents( $file_path );
	
}
