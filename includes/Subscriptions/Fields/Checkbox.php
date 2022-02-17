<?php
namespace Jeero\Subscriptions\Fields;

class Checkbox extends Field {

	protected $choices = array();

	function __construct( $config, $subscription_id, $value = null ) {
		
		parent::__construct( $config, $subscription_id, $value );
		
		if ( !empty( $config[ 'choices' ] ) ) {
			$this->choices = $config[ 'choices' ];
		}
	}

	function get_control_html() {
		
		ob_start();

		foreach( $this->choices as $value => $label ) {
			?><label>
				<input name="<?php echo $this->name; ?>[]" type="checkbox" value="<?php echo $value; ?>"<?php checked( 
					in_array( $value, (array) $this->value ), true, true ); ?>> <?php 
				echo $label; 
			?></label>
			<br><?php

		}

		if ( !empty( $this->instructions ) ) {
			?><p class="description"><?php echo $this->instructions; ?></p><?php
		}

		return ob_get_clean();
		
	}
	
	function get_setting_from_form( ) {
		
		if ( empty( $_GET[ $this->name ] ) ) {
			return null;
		}
		
		$values = array();
		
		for( $c = 0; $c < count( $_GET[ $this->name ] ); $c++ ) {
			$values[] = sanitize_text_field( $_GET[ $this->name ][ $c ] );
		}

		return $values;
		
	}
	
}