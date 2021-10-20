<?php
/**
 * Helpers functions for image handling.
 *
 * @since 1.1
 */
namespace Jeero\Helpers\Images;

const JEERO_IMG_URL_FIELD = 'jeero/img/url';
const MAX_IMAGE_DIMENSIONS = 4000 * 3000;

/**
 * Gets the size of an image.
 *
 * Wrapper function for wp_getimagesize() which was introduced in WP 5.7.
 * Falls back to getimagesize() in earlier WP versions.
 * 
 * @since	1.18
 * @param 	string		$filename	The file path.
 * @return	array|bool				Array of image information or <false> on failure.
 */
function get_imagesize( $filename ) {
	
	if ( function_exists( '\wp_getimagesize' ) ) {
		return \wp_getimagesize( $filename );
	}
	
	return @getimagesize( $filename );
	
}

/**
 * Checks if imagesize is allowed.
 *
 * Checks if image dimensions are within the allowed images dimensions.
 * 
 * @since	1.18
 * @param 	array	$imagesize	Array of image information as returned by getimagesize().
 * @return 	bool
 */
function is_imagesize_allowed( $imagesize ) {
	
	if ( empty( $imagesize[ 0 ] ) ) {
		return false;
	}
	
	if ( empty( $imagesize[ 1 ] ) ) {
		return false;
	}
	
	$dimensions = $imagesize[ 0 ] * $imagesize[ 1 ];
	
	return $dimensions <= MAX_IMAGE_DIMENSIONS;
	
}

/**
 * Updates the post thumbnail from an external image URL.
 * 
 * @since	1.1
 * @param 	int				$post_id
 * @param	string			$url
 * @return	int|WP_Error					The thumbnail ID.
 */
function update_featured_image_from_url( $post_id, $url ) {
	
	$thumbnail_id = add_image_to_library( $url, $post_id  );

	if ( \is_wp_error( $thumbnail_id ) ) {
		return $thumbnail_id;
	}
	
	set_post_thumbnail( $post_id, $thumbnail_id );

	return $thumbnail_id;
	
}

/**
 * Gets an image from the media library by the filtering on the original URL.
 * 
 * @since	1.1
 * @param 	string		$url		The original URL.
 * @return	int|bool			The image ID or <false> if no image is found.
 */
function get_image_by_url( $url ) {
	
	$args = array(
		'post_type' => 'attachment',
		'meta_key' => JEERO_IMG_URL_FIELD,
		'meta_value' => $url,
	);
	
	$images = get_posts( $args );
	
	if ( empty( $images ) ) {
		return false;
	}
	
	return $images[ 0 ]->ID;
	
}

/**
 * Adds an external image to the media library.
 * 
 * @since	1.1
 * @since	1.18	Avoid uploading images that are too big ( max. 4000 pixels wide ).
 *					Cleanup temporary file before returning errors. 
 *
 * @param 	string			$url
 * @param	int				$post_id
 * @param 	string			$name
 * @return	int|WP_Error
 */
function add_image_to_library( $url, $post_id ) {
	
	// Check if image isn't already in media library.
	if ( $thumbnail_id = get_image_by_url( $url ) ) {
		return $thumbnail_id;
	}
	
	require_once( ABSPATH . 'wp-admin/includes/media.php' );
	require_once( ABSPATH . 'wp-admin/includes/file.php' );
	require_once( ABSPATH . 'wp-admin/includes/image.php' );

	$tmp = \download_url( $url );
	if ( \is_wp_error( $tmp ) ) {		
		return new \WP_Error( 'jeero\images', sprintf( 'Unable to download image: %s', $tmp->get_error_message() ) );
	}

	// Determine image dimensions.
	$imagesize = get_imagesize( $tmp );
	
	if ( !$imagesize ) {
		@unlink( $tmp );
		return new \WP_Error( 'jeero\images', sprintf( 'Unable to determine image dimensions of %s.', $url ) );		
	}
	
	if ( !is_imagesize_allowed( $imagesize ) ) {
		@unlink( $tmp );
		return new \WP_Error( 'jeero\images', sprintf( 'Image dimensions of %s not allowed: %dx%d pixels.', $url, $imagesize[ 0 ], $imagesize[ 1 ] ) );
	}

	$extension = get_extension( $tmp );
	
	if ( empty( $extension ) ) {
		@unlink( $tmp );
		return new \WP_Error( 'jeero\images', sprintf( 'Unable to determine extension of %s.', $url ) );
	}

	$path = parse_url( $url, PHP_URL_PATH );
	$basename = pathinfo( $path, PATHINFO_FILENAME );

	$file_array = array(
		'name' => \sanitize_file_name( $basename ).'.'.$extension,
		'tmp_name' => $tmp,
	);

	$thumbnail_id = \media_handle_sideload( $file_array, $post_id );

	if ( \is_wp_error( $thumbnail_id ) ) {
		@unlink( $file_array['tmp_name'] );
		return $thumbnail_id;
	}
	
	// Store original URL with image.
	\update_post_meta( $thumbnail_id, JEERO_IMG_URL_FIELD, $url );
	
	return $thumbnail_id;

}

/**
 * Gets a sanitized extension from an image filename.
 * 
 * @since	1.0
 * @param 	string			$filename
 * @return	string|bool
 */
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
