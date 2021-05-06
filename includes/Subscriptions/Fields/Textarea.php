<?php
namespace Jeero\Subscriptions\Fields;

class Textarea extends Field {
		
	function get_control_html() {
		ob_start();
		?><textarea name="<?php echo $this->name; ?>"<?php
			if ( $this->required ) {
				?> required<?php
		}?> class="large-text code" rows="6"><?php 
			echo esc_html( $this->value ); 
		?></textarea><?php
			
		if ( !empty( $this->instructions ) ) {
			?><p class="description"><?php echo $this->instructions; ?></p><?php
		}
		return ob_get_clean();
	}
	
}