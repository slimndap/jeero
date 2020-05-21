<?php
namespace Jeero\Subscriptions\Fields;

class Url extends Field {
		
	function get_control_html() {
		ob_start();
		?><input type="url" name="<?php echo $this->name; ?>" value="<?php echo $this->value; ?>" class="regular-text"<?php
			if ( $this->required ) {
				?> required<?php
			}?>
		><?php
			
		if ( !empty( $this->instructions ) ) {
			?><p class="description"><?php echo $this->instructions; ?></p><?php
		}
		return ob_get_clean();
	}
	
}