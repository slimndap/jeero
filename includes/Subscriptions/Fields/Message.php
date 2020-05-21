<?php
namespace Jeero\Subscriptions\Fields;

class Message extends Field {
		
	function get_control_html() {
		ob_start();
		?><p><?php echo $this->label; ?></p><?php
		return ob_get_clean();
	}
	
	function get_label() {
		return;
	}
	
}