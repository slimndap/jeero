<?php
namespace Jeero\Subscriptions\Fields;

class Field {
	
	protected $instructions;
	
	protected $label;
	
	protected $name;
	
	protected $required;
	
	protected $subscription_id;
	
	protected $value;
	
	function __construct( $config, $subscription_id, $value = null ) {
		
		foreach( $config as $config_key => $config_value ) {
			$this->{ $config_key } = $config_value;
		}
		
		$this->subscription_id = $subscription_id;
		
		if ( !is_null( $value ) ) {
			$this->value = $value;
		}
		
	}
	
	function get( $key ) {
		return $this->{ $key };
	}
	
	function get_control_html() {
		ob_start();
		?><input type="text" name="<?php echo $this->name; ?>" value="<?php echo $this->value; ?>" class="regular-text"<?php
			if ( $this->required ) {
				?> required<?php
			}?>
		><?php
			
		if ( !empty( $this->instructions ) ) {
			?><p class="description"><?php echo $this->instructions; ?></p><?php
		}
		return ob_get_clean();
	}
	
	/**
	 * Gets the CSS classes for the field.
	 * 
	 * @since	1.5
	 * @return	string[]		The CSS classes for the field.
	 */
	function get_css_classes() {

		$class = new \ReflectionClass( $this );
		
		$classes = array(
			'jeero-field',
			'jeero-field-'.sanitize_title( $class->getShortName() ),
		);
		return $classes;
		
	}
	
	/**
	 * Get the label HTML for the field.
	 * 
	 * @since	1.5
	 * @return	string	The label HTML for the field.
	 */
	function get_label_html() {
		ob_start();

		?><label><?php echo $this->label; ?></label><?php

		return ob_get_clean();
	}
	
	function get_setting_from_form( ) {
		
		if ( empty( $_GET[ $this->name ] ) ) {
			return null;
		}
		return wp_kses_post( $_GET[ $this->name ] );
		
	}
	
	function get_value() {
		return $this->value;
	}
	
	function save_setting( $setting ) {
		echo $setting;
	}
	
}