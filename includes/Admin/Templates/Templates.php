<?php
namespace Jeero\Admin\Templates;

add_action( 'admin_enqueue_scripts', __NAMESPACE__.'\enqueue_scripts', 9 );

/**
 * Enqueues Codemirror scripts on Jeero admin pages.
 * 
 * @since	1.10
 * @return 	void
 */
function enqueue_scripts( ) {

	$current_screen = get_current_screen();	
	if ( 'toplevel_page_jeero/imports' != $current_screen->id ) {
		return;
	}
	
	\wp_enqueue_script( 'jeero/codemirror', \Jeero\PLUGIN_URI . 'assets/js/templates.js', array( 'jquery', 'wp-theme-plugin-editor' ), \Jeero\VERSION );

	$jeero_codemirror = array(
		'settings' => array(
			'codeEditor' =>	wp_enqueue_code_editor(
				array(
					'type' => 'text/html',
					'viewportMargin' => 'Infinity',
				)
			),
		),
		'translations' => array(
			'delete' => __( 'Delete' ),
		),
		'custom_fields' => array(),
		
	);

	$cm_settings[ 'codeEditor' ] = wp_enqueue_code_editor(
		array(
			'type' => 'text/html',
			'viewportMargin' => 'Infinity',
		)
	);
	\wp_localize_script( 'jeero/codemirror', 'jeero_templates', $jeero_codemirror);
	
	\wp_enqueue_style( 'wp-codemirror' );

}

