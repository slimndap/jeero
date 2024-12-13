<?php
/**
 * Helpers functions for image handling.
 *
 * @since 1.1
 */
namespace Jeero\Helpers\Images;

/**
 * Defines the constant for the image ref option name.
 *
 * @since 	1.1
 * @since	1.26	Renamed from JEERO_IMG_URL_FIELD.
 * 
 * @var 	string 	JEERO_IMG_REF_FIELD 	The option name for storing image refs.
 */
const JEERO_IMG_REF_FIELD = 'jeero/img/url';

/**
 * Updates the post thumbnail from an external image URL.
 * 
 * @since		1.1
 * @deprecated	1.26		Use update_featured_image() instead.
 *
 * @param 	int				$post_id
 * @param	string			$url
 * @return	int|WP_Error					The thumbnail ID.
 */
function update_featured_image_from_url( $post_id, $url ) {
	
    // Trigger a deprecation warning.
    _deprecated_function( __FUNCTION__, '1.26', 'update_featured_image()' );

	$structured_image = get_structured_image( $url, $post_id );
	return update_featured_image( $post_id, $structured_image );
	
}

/**
 * Updates the post thumbnail from a structured image.
 * 
 * @since	1.26
 *
 * @param 	int				$post_id
 * @param	array			$structured_image
 * @return	int|WP_Error		The thumbnail ID.
 */
function update_featured_image( $post_id, $structured_image ) {

	$thumbnail_id = add_structured_image_to_library( $structured_image, $post_id  );

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
	
	$structured_image = get_structured_image( $url );	
	return get_existing_thumbnail_for_structured_image( $structured_image );
	
}

/**
 * Gets an image from the media library by the filtering on a structured image ref.
 * 
 * @since	1.26
 * @param 	array		$structured_image		The structured image.
 * @return	int|bool							The image ID or <false> if no image is found.
 */
function get_existing_thumbnail_for_structured_image( $structured_image ) {

	$args = array(
		'post_type' => 'attachment',
		'meta_key' => JEERO_IMG_REF_FIELD,
		'meta_value' => $structured_image[ 'ref' ],
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
 * @since		1.1
 * @since		1.19		Images now get SEO-friendly filenames and alt tags.
 * @deprecated	1.26		Use add_structured_image_to_library() instead.
 *
 * @param 	string			$structured_image_or_url
 * @param	int				$post_id
 * @return	int|WP_Error
 */
function add_image_to_library( $structured_image_or_url, $post_id ) {

    // Trigger a deprecation warning.
    _deprecated_function( __FUNCTION__, '1.26', 'add_structured_image_to_library()' );

	$structured_image = get_structured_image( $structured_image_or_url, $post_id );
	return add_structured_image_to_library( $structured_image, $post_id );
}

/**
 * Adds an external image to the media library.
 * 
 * @since	1.26
 *
 * @param 	array			$structured_image
 * @param	int				$post_id
 * @return	int|WP_Error
 */
function add_structured_image_to_library( $structured_image, $post_id ) {
	
	if ( $thumbnail_id = get_existing_thumbnail_for_structured_image( $structured_image ) ) {
		return $thumbnail_id;
	}		

	require_once( ABSPATH . 'wp-admin/includes/media.php' );
	require_once( ABSPATH . 'wp-admin/includes/file.php' );
	require_once( ABSPATH . 'wp-admin/includes/image.php' );

	$tmp = \download_url( $structured_image[ 'url' ] );
	if ( \is_wp_error( $tmp ) ) {
		return $tmp;	
	}

	$extension = get_extension( $tmp );
	
	$post = get_post( $post_id );
	
	if ( empty( $extension ) ) {
		return new \WP_Error( 'jeero\images', sprintf( 'Failed adding image to the media library. Unable to determine extension of %s.', $structured_image[ 'url' ] ) );
	}

	$file_array = array(
		'name' => sprintf( '%s.%s', $structured_image[ 'basename' ], $extension ),
		'tmp_name' => $tmp,
	);

	$thumbnail_id = \media_handle_sideload( $file_array, $post_id );

	if ( \is_wp_error( $thumbnail_id ) ) {
		@unlink( $file_array['tmp_name'] );
		return $thumbnail_id;
	}
	
	\update_post_meta( $thumbnail_id, '_wp_attachment_image_alt', $structured_image[ 'alt' ] );

	// Store original URL with image.
	\update_post_meta( $thumbnail_id, JEERO_IMG_REF_FIELD, $structured_image[ 'ref' ] );
	
	return $thumbnail_id;

}

/**
 * Gets a sanitized extension from an image filename.
 * 
 * @since	1.0
 * @since	1.20.1	Added GIF support.
 *
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
			case 'image/gif': return 'gif';
			default: return false;
    	}
    	
    }
    	
	return false;
}

/**
 * Gets a valid structured image.
 * 
 * @since	1.26
 *
 * @param 	string|array		$structured_image_or_url		A structured image or the url of an external image.
 * @param 	int				$post_id						The ID of the connected post. 
 * @return	array										A valid structured image
 */

function get_structured_image( $structured_image_or_url, $post_id = false ) {
	
	if ( !is_array( $structured_image_or_url ) ) {

		$structured_image = array();
		
		if ( wp_http_validate_url( $structured_image_or_url ) ) {
			$structured_image[ 'url' ] = $structured_image_or_url;
		}
		
	} else {
		$structured_image = $structured_image_or_url;
	}
	
	if ( empty( $structured_image[ 'ref' ] ) ) {
		if ( !empty( $structured_image[ 'url' ] ) ) {
			$structured_image[ 'ref' ] = $structured_image[ 'url' ];
		}
	}

	if ( $post_id ) {

		$post = get_post( $post_id );
		if ( $post ) {
			if ( empty( $structured_image[ 'alt' ] ) ) {
				$structured_image[ 'alt' ] = $post->post_title;
			}
			if ( empty( $structured_image[ 'basename' ] ) ) {
				$structured_image[ 'basename' ] = sanitize_file_name( $post->post_name );
			}
		}
		
	}
		
	$defaults = array(
		'ref' => '',
		'url' => '',
		'basename' => '',
		'alt' => '',
	);
	
	$structured_image = wp_parse_args( $structured_image, $defaults );

	return $structured_image; 
	
}
