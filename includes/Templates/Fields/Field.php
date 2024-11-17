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
		$this->args        = wp_parse_args( $args, $this->get_defaults() );
		$this->name        = $this->args[ 'name' ];
		$this->label       = $this->args[ 'label' ] ?: $this->name;
		$this->description = $this->args[ 'description' ];
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
		$variable_parts = $prefix;
		// Prevent duplicate field names in the prefix
		if ( empty( $prefix ) || end( $prefix ) !== $this->name ) {
			$variable_parts[] = $this->name;
		}
		$variable = implode( '.', $variable_parts );
		return $this->indent_example( "{{ $variable }}", $indent );
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
				'name'        => implode( '.', array_merge( $prefix, array( $this->name ) ) ),
				'description' => $this->get_description(),
				'example'     => false,
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

	protected function indent_example( $content, $indent ) {
		$lines    = explode( "\n", $content );
		$indented = array_map( function( $line ) use ( $indent ) {
			return str_repeat( "\t", $indent ) . $line;
		}, $lines );
		return implode( "\n", $indented );
	}
}
