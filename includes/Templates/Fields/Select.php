<?php
/**
 * Select template field.
 * @since	1.10
 */
namespace Jeero\Templates\Fields;

class Select extends Field {
	
	private $sub_fields;
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
	 *
	 * @return	string
	 */
	function get_example( $prefix = array(), $indent = 0 ) {
		
		ob_start();

?>{% if <?php echo $this->name; ?> %}
<h3><?php echo $this->label; ?></h3>
<ul>
	{% for <?php echo $this->item[ 'name' ]; ?> in <?php echo $this->name; ?> %}
		<li>
<?php
	$field = get_field_from_config( $this->item );
	echo $field->get_example( $prefix, $indent + 3 );
?>

		</li>
	{% endfor %}
</ul>
{% endif %}<?php
	
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
				'example' => $this->get_example( $prefix ),
			),
		);
		
	}

	
}