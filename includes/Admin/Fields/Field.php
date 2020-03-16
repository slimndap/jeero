<?php
namespace Jeero\Admin\Fields;

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
	
	function get_label() {
		return $this->label;
	}
	
	function get_setting_from_form( $data ) {
		
		if ( empty( $data[ $this->name ] ) ) {
			return null;
		}
		return $data[ $this->name ];
		
	}
	
	function save_setting( $setting ) {
		echo $setting;
	}
	
}