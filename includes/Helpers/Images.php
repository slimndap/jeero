<?php
/**
 * Helpers functions for image handling.
 *
 * @since 1.1
 */
namespace Jeero\Helpers\Images;

const JEERO_IMG_URL_FIELD = 'jeero/img/url';

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
 * @since	1.19	Images now get SEO-friendly filenames and alt tags.
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
		return $tmp;	
	}

	$extension = get_extension( $tmp );
	
	$post = get_post( $post_id );
	
	if ( empty( $extension ) ) {
		return new \WP_Error( 'jeero\images', sprintf( 'Failed adding image to the media library. Unable to determine extension of %s.', $url ) );
	}

	$file_array = array(
		'name' => sprintf( '%s.%s', \sanitize_file_name( $post->post_name ), $extension ),
		'tmp_name' => $tmp,
	);

	$thumbnail_id = \media_handle_sideload( $file_array, $post_id );

	if ( \is_wp_error( $thumbnail_id ) ) {
		@unlink( $file_array['tmp_name'] );
		return $thumbnail_id;
	}
	
	\update_post_meta( $thumbnail_id, '_wp_attachment_image_alt', $post->post_title );

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
