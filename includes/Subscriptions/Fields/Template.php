<?php
/**
 * Template settings field.
 * @since	1.10
 */
namespace Jeero\Subscriptions\Fields;

class Template extends Textarea {
		
	function get_setting_from_form( ) {
		
		if ( empty( $_GET[ $this->name ] ) ) {
			return null;
		}
		
		return stripslashes( $_GET[ $this->name ] );
		
	}
	
}