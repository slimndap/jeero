<?php
/**
 * Manages all Theaters on Mother's list.
 */
namespace Jeero\Theaters;

add_action( 'init', __NAMESPACE__.'\add_import_actions' );

function add_import_actions() {
	
	$theaters = get_theaters();
	foreach( $theaters as $theater ) {		
		add_action( 'jeero/inbox/process/item/import/theater='.$theater->get( 'slug' ), array( $theater, 'import' ), 10, 3 );		
	}
	
}

function get_theaters() {
	return array();
}

/**
 * Get a theater object by name when a local theater class is available.
 *
 * @since 1.34
 *
 * @param string $theater_name Theater name or slug.
 * @return Theater|null
 */
function get_theater( string $theater_name ) {

	$theater_name = sanitize_key( $theater_name );

	if ( '' === $theater_name ) {
		return null;
	}

	$class_name = __NAMESPACE__ . '\\' . str_replace( ' ', '_', ucwords( str_replace( array( '-', '_' ), ' ', $theater_name ) ) );

	if ( ! class_exists( $class_name ) || ! is_subclass_of( $class_name, Theater::class ) ) {
		return null;
	}

	return new $class_name();

}
