<?php
/**
 * Manages all Theaters on Mother's list.
 */
namespace Jeero\Theaters;

add_action( 'init', __NAMESPACE__.'\add_import_actions' );

function add_import_actions() {
	
	$theaters = get_theaters();
	foreach( $theaters as $theater ) {		
		add_action( 'jeero/inbox/process/item/import/theater='.$theater->get( 'slug' ), array( $theater, 'import' ), 10, 3 );		
	}
	
}

function get_theaters() {
	return array();
}

