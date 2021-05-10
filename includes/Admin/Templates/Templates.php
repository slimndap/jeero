<?php
namespace Jeero\Admin\Templates;

add_action( 'admin_enqueue_scripts', __NAMESPACE__.'\enqueue_scripts', 9 );

function enqueue_scripts( ) {

	$current_screen = get_current_screen();	
	if ( 'toplevel_page_jeero/imports' != $current_screen->id ) {
		return;
	}
	
	$cm_settings[ 'codeEditor' ] = wp_enqueue_code_editor(
		array(
			'type' => 'text/html'
		)
	);
	wp_localize_script( 'jquery', 'cm_settings', $cm_settings);
	
	wp_enqueue_script( 'jeero/codemirror', \Jeero\PLUGIN_URI . 'assets/js/templates.js', array( 'jquery', 'wp-theme-plugin-editor' ), \Jeero\VERSION );

	wp_enqueue_style( 'wp-codemirror' );

}

