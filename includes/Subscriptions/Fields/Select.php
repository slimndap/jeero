<?php
namespace Jeero\Subscriptions\Fields;

class Select extends Field {

	protected $choices = array();

	function __construct( $config, $subscription_id, $value = null ) {
		
		parent::__construct( $config, $subscription_id, $value );
		
		if ( !empty( $config[ 'choices' ] ) ) {
			$this->choices = $config[ 'choices' ];
		}
	}

	function get_control_html() {
		
		ob_start();
		?><select name="<?php echo $this->name; ?>"<?php
			if ( $this->required ) {
				?> required<?php
			}?>
		><?php
			foreach( $this->choices as $value => $label ) {
				?><option value="<?php echo $value; ?>"<?php selected( $this->value, $value, true ); ?>><?php echo $label; ?></option><?php
			}
		?></select><?php

		if ( !empty( $this->instructions ) ) {
			?><p class="description"><?php echo $this->instructions; ?></p><?php
		}

		return ob_get_clean();
		
	}
	
}