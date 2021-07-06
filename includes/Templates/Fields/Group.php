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
	 * @since	1.15.4	Added support for prefix.
	 *					Added example code for sub fields.
	 *
	 * @return	string
	 */
	function get_example( $prefix = array(), $indent = 0 ) {
		
		ob_start();
?>
<h3><?php 
		echo $this->label; 
?></h3>
<?php		
		$prefix[] = $this->name;
		foreach( $this->sub_fields as $sub_field_args ) {
			$sub_field = get_field_from_config( $sub_field_args );
?>

<?php 
			echo $sub_field->get_example( $prefix, $indent );
		}
			
		return $this->indent_example( ob_get_clean(), $indent );
	}
	
	/**
	 * Gets the template variables of the field.
	 * 
	 * @since	1.10
	 * @return	array
	 */
	function get_variables( $prefix = array(), $indent = 0 ) {
		return array(
			array(
				'name' => $this->name,
				'description' => $this->get_description(),
				'example' => $this->get_example( $prefix, $indent ),
			),
		);
		
	}
	
}