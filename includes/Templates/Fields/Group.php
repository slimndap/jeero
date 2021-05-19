<?php
/**
 * Group template field.
 * @since	1.10
 */
namespace Jeero\Templates\Fields;

class Group extends Field {
	
	private $sub_fields;

	function __construct( $args ) {
		
		parent::__construct( $args );
		
		$this->sub_fields = $this->args[ 'sub_fields' ];
	}
	
	/**
	 * Gets the default values for the field args.
	 * 
	 * @since	1.10
	 * @return	array
	 */
	function get_defaults() {
		
		return array(
			'name' => '',
			'label' => '',
			'description' => '',
			'sub_fields' => array(),
		);
		
	}
	
	/**
	 * Gets the description of the field.
	 * 
	 * @since	1.10
	 * @return	string
	 */
	function get_description() {
		
		$description = sprintf( '%s (%s)', parent::get_description(), __( 'object', 'jeero' ) );
		
		return $description;
	}
	
	/**
	 * Gets the example template usage of the field.
	 * 
	 * @since	1.10
	 * @since	1.11		Fixed a PHP warning if a sub field did not have a 'type' value.
	 * @return	string
	 */
	function get_example( $indent = 0 ) {
		
		ob_start();
?>
<h3><?php echo $this->label; ?></h3>
<?php		
		foreach( $this->sub_fields as $sub_field_args ) {
			$sub_field = get_field_from_config( $sub_field_args );
?>
<div><?php echo $sub_field->label; ?>: {{ <?php echo $this->name; ?>.<?php echo $sub_field->name; ?> }}</div>
<?php
		}
			
		return $this->indent_example( ob_get_clean(), $indent );
	}
	
	/**
	 * Gets the template variables of the field.
	 * 
	 * @since	1.10
	 * @return	array
	 */
	function get_variables( $prefix = '' ) {
		return array(
			array(
				'name' => $prefix.$this->name,
				'description' => $this->get_description(),
				'example' => $this->get_example(),
			),
		);
		
	}
	
}