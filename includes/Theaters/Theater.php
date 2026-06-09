<?php
namespace Jeero\Theaters;

/**
 * Theater class.
 * 
 */
class Theater {

	/**
	 * ID
	 * @var int ID
	 */
	public $ID;
	
	public $display_name;

	/**
	 * Get the globally known widget names supported by this theater.
	 *
	 * Return null when the theater does not declare widget support. This keeps
	 * existing theater integrations compatible until they opt in explicitly.
	 *
	 * @since 1.34
	 *
	 * @return string[]|null
	 */
	public function get_supported_widgets(): ?array {

		return null;

	}
	
}
