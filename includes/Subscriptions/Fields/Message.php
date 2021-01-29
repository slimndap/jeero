<?php
namespace Jeero\Subscriptions\Fields;

class Message extends Field {
		
	function get_control_html() {
		ob_start();
		?><p><?php echo $this->label; ?></p><?php
		return ob_get_clean();
	}
	
	/**
	 * Get the label HTML for message fields.
	 * 
	 * @since	1.5
	 * @return	string	The label HTML for message fields.
	 */
	function get_label_html() {
		return;
	}
	
}