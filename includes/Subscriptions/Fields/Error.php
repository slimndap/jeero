<?php
namespace Jeero\Subscriptions\Fields;

class Error extends Field {
		
	function get_control_html() {
		ob_start();
		echo $this->value;
		return ob_get_clean();
	}
	
}