<?php
/**
 * Select template field.
 * @since	1.10
 */
namespace Jeero\Templates\Fields;

class Select extends Field {

	private $item;

	function __construct( $args ) {
		
		parent::__construct( $args );
		
		$this->item = $this->args[ 'item' ];
	}
	
	function get_description() {
		
		$description = sprintf( '%s (%s)', parent::get_description(), __( 'list', 'jeero' ) );
		
		return $description;
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
			'item' => false,
		);
		
	}
	
	/**
	 * Gets the example template usage of the field.
	 * 
	 * @since	1.10
	 * @since	1.15.4	Added support for prefix.
	 * @since	1.30.4	Prevent duplicate field names in the prefix when field is a nested field.
	 *
	 * @return	string
	 */

	function get_example( $prefix = array(), $indent = 0 ) {
		// Construct the full variable name with prefix
		$full_name = implode( '.', array_merge( $prefix, array( $this->name ) ) );
		$item_var  = $this->item[ 'name' ];

		ob_start();
?>
{% if <?php echo $full_name; ?> %}
<h3><?php echo $this->label; ?></h3>
<ul>
	{% for <?php echo $item_var; ?> in <?php echo $full_name; ?> %}
		<li>
<?php
		// Reset the prefix to the loop variable for sub-fields
		$field = get_field_from_config( $this->item );
		echo $field->get_example( array( $item_var ), $indent + 3 );
?>

		</li>
	{% endfor %}
</ul>
{% endif %}
<?php
		return $this->indent_example( ob_get_clean(), $indent );
	}

	/**
	 * Gets the template variables of the field.
	 * 
	 * @since	1.10
	 * @since	1.30.4	Include prefix in the variable name when field is a nested field.
	 * @return	array
	 */
	function get_variables( $prefix = array() ) {
		// Include prefix in the variable name
		return array(
			array(
				'name'        => implode( '.', array_merge( $prefix, array( $this->name ) ) ),
				'description' => $this->get_description(),
				'example'     => $this->get_example( $prefix ),
			),
		);
	}

}
