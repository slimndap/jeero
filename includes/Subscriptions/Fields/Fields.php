<?php
namespace Jeero\Subscriptions\Fields;

function get_field_from_config( $config, $subscription, $value = null ) {
	
	$classname = __NAMESPACE__.'\\'.$config[ 'type' ];
	return get_field_from_classname( $classname, $config, $subscription, $value);
	
}

function get_field_from_classname( $classname, $config, $subscription, $value = null ) {

	if ( class_exists( $classname ) ) {
		return new $classname( $config, $subscription, $value );
	}
	return new Field( $config, $subscription, $value );
	
}