<?php
namespace Jeero\Templates\Fields;

class Field {
	
	protected $args;
	
	protected $name;
	
	protected $description;
	
	protected $label;
	
	function __construct( $args ) {
		
		$args = wp_parse_args( $args, $this->get_defaults() );
		
		$this->args = $args;
		$this->name = $args[ 'name' ];
		$this->label = $args[ 'label' ];
		$this->description = $args[ 'description' ];
	}
	
	function get( $name ){
		return $this->$name;
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
			'jeero-template-field',
			'jeero-template-field-'.sanitize_title( $class->getShortName() ),
		);
		return $classes;
		
	}
	
	function get_defaults() {
		
		return array(
			'name' => '',
			'label' => '',
			'description' => '',
		);
		
	}
	
	function get_description() {
		return $this->description;
	}
	
	function get_example( $indent = 0 ) {
		
		ob_start();
?>{{ <?php echo $this->name; ?> }}<?php
		return $this->indent_example( ob_get_clean(), $indent );
				
	}
	
	function get_variables( $prefix = '' ) {
		return array(
			array(
				'name' => $prefix.$this->name,
				'description' => $this->get_description(),
				'example' => false,
			),
		);
		
	}
	
	function indent_example( $example, $indent ) {

		$lines = explode( "\n", $example );
		for( $l = 0; $l < count( $lines ); $l++ ) {
			$lines[ $l ] = str_repeat( "\t", $indent ).$lines[ $l ];
		}
		
		return implode( "\n", $lines );
		
	}
	
}