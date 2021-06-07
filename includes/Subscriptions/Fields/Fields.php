<?php
namespace Jeero\Subscriptions\Fields;

function get_field_from_config( $config, $subscription_id, $value = null ) {
	
	$classname = '';
	
	if ( !empty( $config[ 'type' ] ) ) {
		$classname = __NAMESPACE__.'\\'.$config[ 'type' ];	
	}
	return get_field_from_classname( $classname, $config, $subscription_id, $value);
	
}

function get_field_from_classname( $classname, $config, $subscription_id, $value = null ) {

	if ( class_exists( $classname ) ) {
		return new $classname( $config, $subscription_id, $value );
	}
	return new Field( $config, $subscription_id, $value );
	
}