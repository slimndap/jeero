<?php
namespace Jeero\Plans;

function get_fields() {
	
	$fields = array(
		array( 
			'type' => 'Tab',
			'label' => 'Plans',
			'name' => 'plans',
		),
		array(
			'type' => 'message',
			'label' => 'hallo',
			'name' => 'hallo',
		)
	);
	
	return $fields;
}
