<?php
/**
 * Handles all communication with the custom Jeero tables in the database.
 */
namespace Jeero\Db;

/**
 * Upgrades the Jeero tables to version 1.0.
 * 
 * @since	1.0
 * @return	bool
 */
function upgrade_100() {

	global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE `{$wpdb->base_prefix}jeero_subscriptions` (
      `ID` varchar(191) NOT NULL,
      `settings` longtext,
      `next_delivery` datetime,
      PRIMARY KEY  (`ID`)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
    $success = empty( $wpdb->last_error );
    
    return $success;	
    
}

/**
 * Upgrades the Jeero tables to the most recente version.
 *  
 * @return void
 */
function upgrade() {
	
	$version = (int) get_option( 'Jeero/Db/Version' );
    if ( $version < 101 && upgrade_100() ) {
        $result = update_site_option( 'Jeero/Db/Version', 100);
    }
    
}
add_action( 'init', __NAMESPACE__.'\upgrade' );