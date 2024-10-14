<?php
namespace Jeero\Admin\Templates;

add_action( 'admin_enqueue_scripts', __NAMESPACE__.'\enqueue_scripts', 9 );

/**
 * Enqueues templates scripts on Jeero admin pages.
 * 
 * @since	1.10
 * @since	1.14	Added support for custom fields.	
 * @return 	void
 */
function enqueue_scripts( ) {

	$current_screen = get_current_screen();	

	if ( 'jeero_page_jeero/imports' != $current_screen->id ) {
		return;
	}
	
	\wp_enqueue_script( 'jeero/templates', \Jeero\PLUGIN_URI . 'assets/js/templates.js', array( 'jquery', 'wp-theme-plugin-editor' ), \Jeero\VERSION );

	$jeero_templates = array(
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

	\wp_localize_script( 'jeero/templates', 'jeero_templates', $jeero_templates);
	
	\wp_enqueue_style( 'wp-codemirror' );

}

