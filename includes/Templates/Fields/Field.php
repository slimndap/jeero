<?php
/**
 * Base template field.
 * @since	1.10
 */
namespace Jeero\Templates\Fields;

class Field {
	
	/**
	 * The field arguments.
	 * 
	 * @since	1.10
	 */
	protected $args;
	
	/**
	 * The field name.
	 * 
	 * @since	1.10
	 */
	protected $name;
	
	/**
	 * The field label.
	 * 
	 * @since	1.10
	 */
	protected $label;
	
	/**
	 * The field description.
	 * 
	 * @since	1.10
	 */
	protected $description;
	
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
	 * @since	1.10
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
		);
		
	}
	
	/**
	 * Gets the description of the field.
	 * 
	 * @since	1.10
	 * @return	string
	 */
	function get_description() {
		return $this->description;
	}
	
	/**
	 * Gets the example template usage of the field.
	 * 
	 * @since	1.10
	 * @since	1.15.4	Added support for prefix.
	 *
	 * @return	string
	 */
	function get_example( $prefix = array(), $indent = 0 ) {

		ob_start();
?>{{ <?php 
		if ( !empty( $prefix ) ) {
			echo implode( '.', $prefix); ?>.<?php
		}
		echo $this->name; ?> }}<?php
		return $this->indent_example( ob_get_clean(), $indent );
				
	}
	
	/**
	 * Gets the template variables of the field.
	 * 
	 * @since	1.10
	 * @return	array
	 */
	function get_variables( $prefix = array() ) {
		return array(
			array(
				'name' => $this->name,
				'description' => $this->get_description(),
				'example' => false,
			),
		);
		
	}
	
	/**
	 * Indents the template example.
	 * 
	 * @since	1.10
	 * @param 	string	$example
	 * @param	int		$indent
	 * @return	string
	 */
	function indent_example( $example, $indent ) {

		$lines = explode( "\n", $example );
		for( $l = 0; $l < count( $lines ); $l++ ) {
			$lines[ $l ] = str_repeat( "\t", $indent ).$lines[ $l ];
		}
		
		return implode( "\n", $lines );
		
	}
	
}