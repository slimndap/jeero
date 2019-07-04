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
      ID varchar(191) NOT NULL,
      status varchar(191),
      fields longtext,
      settings longtext,
      next_update datetime,
      PRIMARY KEY  (ID)
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

    if ( $version < 100 && upgrade_100() ) {
        update_site_option( 'Jeero/Db/Version', 100);
    }
    
}
add_action( 'init', '\Jeero\Db\upgrade' );