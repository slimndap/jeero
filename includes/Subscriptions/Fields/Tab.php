<?php
namespace Jeero\Subscriptions\Fields;

/**
 * Tab field class.
 * 
 * @since	1.5
 * @extends	Field
 */
class Tab extends Field {
		
	/**
	 * Gets the control HTML for tab fields.
	 * 
	 * @since	1.5
	 * @return	string
	 */
	function get_control_html() {
		ob_start();
		echo $this->value;
		return ob_get_clean();
	}
	
}