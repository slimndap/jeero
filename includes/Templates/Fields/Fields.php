<?php
namespace Jeero\Templates\Fields;

function get_field_from_config( $config ) {

	$defaults = array(
		'type' => 'text',
	);
	
	$config = \wp_parse_args( $config, $defaults );
	
	$classname = __NAMESPACE__.'\\'.$config[ 'type' ];
	return get_field_from_classname( $classname, $config );
	
}

function get_field_from_classname( $classname, $args = array() ) {
	
	if ( class_exists( $classname ) ) {
		return new $classname( $args );
	}
	return new Field( $args );
	
}

function parse_args() {
	
}