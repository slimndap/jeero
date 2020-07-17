<?php
namespace Jeero\Calendars;

add_action( 'init', __NAMESPACE__.'\add_import_filters' );

function add_import_filters() {
	
	$calendars = get_active_calendars();
	foreach( $calendars as $calendar ) {		
		add_filter( 'jeero/inbox/process/item/import/calendar='.$calendar->get( 'slug' ), array( $calendar, 'import' ), 10, 4 );		
	}
	
}

function get_active_calendars() {
	
	$slugs = array();
	
	if ( class_exists( 'WP_Theatre' ) ) {
		$slugs[] = 'Theater_For_WordPress';
	}
	
	if ( class_exists( 'Tribe__Events__Main' ) ) {
		$slugs[] = 'The_Events_Calendar';
	}
	
	if ( class_exists( 'Ai1ec_Front_Controller' ) ) {
		$slugs[] = 'All_In_One_Event_Calendar';
	}
		
	$calendars = array();
	
	foreach ( $slugs as $slug ) {
		
		$calendars[] = get_calendar( $slug );
		
	}
	
	return $calendars;
	
}

function get_calendar( $slug = '' ) {
	
	$class = __NAMESPACE__.'\\'.$slug;
	if ( class_exists( $class ) ) {
		return new $class();
	}

	return new Calendar();	
	
}

function add_featured_image( $url, $post_id, $name ) {
	
	$thumbnail_id = add_image_to_library(  $url, $post_id, $name  );

	if ( \is_wp_error( $thumbnail_id ) ) {
		error_log( sprintf( 'Updating thumbnail for event failed %s / %d.', $name, $post_id ) );
		return;
	}
	
	set_post_thumbnail( $post_id, $thumbnail_id );
	
}

function add_image_to_library( $url, $post_id, $name ) {
	
	require_once( ABSPATH . 'wp-admin/includes/media.php' );
	require_once( ABSPATH . 'wp-admin/includes/file.php' );
	require_once( ABSPATH . 'wp-admin/includes/image.php' );

	$tmp = \download_url( $url );
	if ( \is_wp_error( $tmp ) ) {
		return $tmp;	
	}

	$extension = get_extension( $tmp );
	
	if ( empty( $extension ) ) {
		return;
	}

	$file_array = array(
		'name' => \sanitize_file_name( $name ).'.'.$extension,
		'tmp_name' => $tmp,
	);

	$thumbnail_id = \media_handle_sideload( $file_array, $post_id, $name );

	if ( \is_wp_error( $thumbnail_id ) ) {
		@unlink( $file_array['tmp_name'] );
	}
	
	return $thumbnail_id;

}

function get_extension( $filename ) {

	$img = getimagesize( $filename );

    if ( !empty( $img[2] ) ) {
	    
	    $mimetype = image_type_to_mime_type( $img[2] );

    	switch( $mimetype ) {
			case 'image/jpeg': return 'jpg';
			case 'image/png': return 'png';
			default: return false;
    	}
    	
    }
    	
	return false;
}
	