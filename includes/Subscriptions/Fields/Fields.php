<?php
namespace Jeero\Subscriptions\Fields;

/**
 * Gets a the proper field object from the field config.
 * 
 * @since	1.?
 * @since	1.14	Fixed a PHP waring if the config has no 'type' defined.
 *
 * @param	array	$config
 * @param	string	$subscription_id
 * @param	mixes	$value (default: null)
 *
 * @return 	\Jeero\Subscriptions\Fields\Field
 */
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