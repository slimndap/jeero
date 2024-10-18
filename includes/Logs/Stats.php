<?php
/**
 * Handles stats.
 *
 * @since	1.30
 *
 */
namespace Jeero\Logs\Stats;

const STATS_UID_KEY = 'jeero_stats_uid_key';
const STATS_MAX_HOURS = 6;

/**
 * Attaches stat import to inbox filter.
 * @since	1.30.1
 */
add_filter( 'jeero/inbox/process/item/stat', __NAMESPACE__.'\add_stat_from_inbox', 10, 5 );

/**
 * Adds a stat to the stats file.
 *
 * @since	1.30
 *
 * @param	$name	string
 * @param	$value	mixed
 * @return			void
 */
function add_stat( $name, $value ) {

    if ( !\get_option( 'jeero/enable_logs' ) ) {
	    return;
	}
	
	$file_path = get_file_path( );
	
	if ( \is_wp_error( $file_path ) ) {
		return $file_path;
	}

	$stats = get_stats();

	$stats[] = array(
		'time' => get_time()->format( 'c' ),
		'name' => $name,
		'value' => $value,
	);
	
	$stats = trim_stats( $stats );

	file_put_contents( $file_path, json_encode( $stats ) );
	
}

/**
 * Adds a stats from the inbox.
 * 
 * @since	1.30.1
 */
function add_stat_from_inbox( $result, $data, $raw, $theater, $subscription ) {

	if ( is_wp_error( $result ) ) {
		return $result;
	}
	
	foreach( $data as $key => $value ) {
		add_stat( sprintf( 'remote_%s', $key ), $value );
		
	}
	
}

/**
 * Trim the stats by removing all entries that are older then STATS_MAX_HOURS hours.
 *
 * @since	1.30
 *
 * @param	$stats	array
 * @return			array
 */
function trim_stats( $stats ) {
	
	$trimmed_stats = array();
	
	$current_time = new \DateTime();
	
	foreach ( $stats as $stat ) {
	
		$stat_time = new \DateTime( $stat[ 'time' ] );
		
		$interval = $current_time->diff( $stat_time );
		
		// Calculate total hours
		$total_hours = ( $interval->days * 24 ) + $interval->h;
		
		// Check if the stat is within the last STATS_MAX_HOURS hours
		if ( $total_hours <= STATS_MAX_HOURS ) {
			$trimmed_stats[] = $stat;
		}
		
	}
	
	return $trimmed_stats;
	
}

/**
 * Gets the path to the Jeero stats.
 * 
 * @since	1.30
 *
 * @return	string 	The path to the Jeero stats.
 */
function get_file_path( ) {

	$upload_dir = get_upload_dir();
	
	if ( \is_wp_error( $upload_dir ) ) {
		return $upload_dir;
	}
	
	return sprintf( '%s%s.json', $upload_dir, get_uid() );
	
}

/**
 * Gets the current time.
 * 
 * @since	1.30
 *
 * @return	DateTime
 */
function get_time() {

	$timezone_string = get_option('timezone_string');
	if (empty( $timezone_string ) ) {
	    $gmt_offset = get_option( 'gmt_offset' );
	    $timezone_string = $gmt_offset ? timezone_name_from_abbr( '', $gmt_offset * 3600, 0 ) : 'UTC';
	}			

	$time = new \DateTime( );
	$time->setTimezone( new \DateTimeZone( $timezone_string ) );			
	
	return $time;
}

/**
 * Gets the path to the upload dir for the Jeero stats.
 * 
 * @since	1.30
 * @return	string	The path to the upload dir.
 */
function get_upload_dir() {

	return create_upload_dir();

}

/**
 * Gets all stats.
 * 
 * @since	1.30
 * @return	array
 */
function get_stats() {
	
	$file_path = get_file_path( );
	
	if ( \is_wp_error( $file_path ) ) {
		return $file_path;
	}

	if ( file_exists( $file_path ) ) {
		$stats = json_decode( file_get_contents( $file_path ), true );
	} else {
		$stats = array();
	}

	return $stats;

}

/**
 * Adds a stat to the cache.
 *
 * @since 	1.30.1
 * @param	$name	string
 * @param	$value	mixed
 */
function cache_set( $name, $value ) {
	return wp_cache_set( $name, $value, __NAMESPACE__ );
}

/**
 * Gets a stat from the cache.
 *
 * @since 	1.30.1
 * @param	$name	string
 */
function cache_get( $name ) {
	return wp_cache_get( $name, __NAMESPACE__ );
}

/**
 * Gets the UUID used for the Jeero stats filename.
 * 
 * @since	1.30
 * @return	string	The UUID used for the Jeero stats filename.
 */
function get_uid( ) {
	
	$uid = get_option( STATS_UID_KEY );
	
	if ( empty( $uid ) ) {
		
		$uid = \wp_generate_uuid4();
		update_option( STATS_UID_KEY, $uid, false );
		
	}
	
	return $uid;
	
}

/**
 * Creates the upload dir for the Jeero stats.
 * 
 * @since	1.30
 *
 * @return	string	The path to the upload dir.
 */
function create_upload_dir() {

	$wp_upload_dir = \wp_upload_dir();
	$upload_dir_path = sprintf( '%s/jeero/stats/', $wp_upload_dir['basedir'] );

	if ( is_dir( $upload_dir_path ) ) {
		return $upload_dir_path;
	}
	
	if ( !wp_mkdir_p( $upload_dir_path ) ) {
		return new WP_Error( 'stats', sprintf( 'Unable to create %s folder for Jeero stats', $upload_dir_path ) );
	}
	
	$index_php_path = $upload_dir_path.'index.php';
	$index_php_content = '<?php // Silence is golden';
	file_put_contents($index_php_path, $index_php_content );

	$htaccess_path = $upload_dir_path.'.htaccess';
	$htaccess_content = '<Files jeero.stats>deny from all</Files>';
	file_put_contents($htaccess_path, $htaccess_content );
	
	return $upload_dir_path;
}
