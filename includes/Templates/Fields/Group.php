<?php
namespace Jeero\Templates\Fields;

class Group extends Field {
	
	private $sub_fields;

	function __construct( $args ) {
		
		parent::__construct( $args );
		
		$this->sub_fields = $this->args[ 'sub_fields' ];
	}
	
	function get_defaults() {
		
		return array(
			'name' => '',
			'label' => '',
			'description' => '',
			'sub_fields' => array(),
		);
		
	}
	
	function get_description() {
		
		$description = sprintf( '%s (%s)', parent::get_description(), __( 'object', 'jeero' ) );
		
		return $description;
	}
	
	function get_example( $indent = 0 ) {
		
		ob_start();
?>
<h3><?php echo $this->label; ?></h3>
<?php		
		foreach( $this->sub_fields as $sub_field_args ) {
			$sub_field = get_field_from_classname( $sub_field_args[ 'type' ], $sub_field_args );
?>
<div><?php echo $sub_field->label; ?>: {{ <?php echo $this->name; ?>.<?php echo $sub_field->name; ?> }}</div>
<?php
		}
			
		return $this->indent_example( ob_get_clean(), $indent );
	}
	
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