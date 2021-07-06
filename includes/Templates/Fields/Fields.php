<?php
/**
 * Handles template fields.
 *
 * @since	1.10
 */
namespace Jeero\Templates\Fields;

/**
 * Get a field from a field config.
 * 
 * @since	1.10
 * @param	array	$config
 * @return	\Jeero\Templates\Fields\Field
 */
function get_field_from_config( $config ) {

	$defaults = array(
		'type' => 'text',
	);
	
	$config = \wp_parse_args( $config, $defaults );
	
	$classname = __NAMESPACE__.'\\'.$config[ 'type' ];
	return get_field_from_classname( $classname, $config );
	
}

/**
 * Get a field from a class name.
 * 
 * @since	1.10
 * @param	string	$classname
 * @param	array	$args		The field args.
 * @return	\Jeero\Templates\Fields\Field
 */
function get_field_from_classname( $classname, $args = array() ) {
	
	if ( class_exists( $classname ) ) {
		return new $classname( $args );
	}
	return new Field( $args );
	
}