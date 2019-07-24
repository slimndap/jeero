<?php
namespace Jeero\Admin\Fields;

function get_field( $config, $subscription_id, $value = null ) {
	
	$class = __NAMESPACE__.'\\'.$config[ 'type' ];

	if ( class_exists( $class ) ) {
		return new $class( $config, $subscription_id, $value );
	}
	return new Field( $config, $subscription_id, $value );
}