<?php
namespace Jeero\Templates\Fields;

class Select extends Field {
	
	private $sub_fields;

	function __construct( $args ) {
		
		parent::__construct( $args );
		
		$this->item = $this->args[ 'item' ];
	}
	
	function get_description() {
		
		$description = sprintf( '%s (%s)', parent::get_description(), __( 'list', 'jeero' ) );
		
		return $description;
	}
	
	function get_defaults() {
		
		return array(
			'name' => '',
			'label' => '',
			'description' => '',
			'item' => false,
		);
		
	}
	
	function get_example( $indent = 0 ) {
		
		ob_start();
		
?>{% if <?php echo $this->name; ?> %}
<h3><?php echo $this->label; ?></h3>
<ul>
	{% for <?php echo $this->item[ 'name' ]; ?> in <?php echo $this->name; ?> %}
		<li>
<?php
	$field = get_field_from_config( $this->item );
	echo $field->get_example( $indent + 3 );
?>

		</li>
	{% endfor %}
</ul>
{% endif %}<?php
	
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