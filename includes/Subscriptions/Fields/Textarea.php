<?php
/**
 * Textarea settings field.
 * @since	1.10
 */
namespace Jeero\Subscriptions\Fields;

class Textarea extends Field {
	
	protected $rows = 6;
	
	function get_control_html() {
		ob_start();
		?><textarea name="<?php echo $this->name; ?>"<?php
			if ( $this->required ) {
				?> required<?php
		}?> class="large-text code" rows="<?php echo $this->get( 'rows' ); ?>"><?php 
			
			echo esc_html( $this->get_value() ); 			
			
		?></textarea><?php
			
		if ( !empty( $this->instructions ) ) {
			?><p class="description"><?php echo $this->instructions; ?></p><?php
		}
		return ob_get_clean();
	}
	
	function get_setting_from_form( ) {
		
		if ( empty( $_GET[ $this->name ] ) ) {
			return null;
		}
		
		return sanitize_textarea_field( stripslashes( $_GET[ $this->name ] ) );
		
	}
	
}