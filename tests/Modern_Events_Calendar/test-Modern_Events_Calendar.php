<?php
class Modern_Events_Calendar_Test extends Post_Based_Calendar_Test {
	
	function __construct() {
		
		parent::__construct();
		
		$this->calendar = 'Modern_Events_Calendar';
		
	}

	function test_excerpt_is_updated_after_second_import() {
		// Skip this test. Modern Events Calendar does not support excerpts.
	}
	
}