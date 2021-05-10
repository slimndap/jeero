<?php
/**
 * Handles the templates.
 *
 * @since	1.10
 */
namespace Jeero\Templates;

/**
 * Renders a Twig template.
 * 
 * @since	1.0
 * @param	string			$template	The Twig template.
 * @param 	array			$data		The template data.
 * @return	string|WP_Error				The rendered Twig template.
 *										Or WP_Error if there is a syntax error in the Twig template.
 */
function render( $template, $data ) {

	$loader = new \Twig\Loader\ArrayLoader( array(
	    'jeero.html' => $template,
	) );
	
	// Replace built-in 'date' filter to add support for i18n dates.
	$date_filter = new \Twig\TwigFilter( 'date', __NAMESPACE__.'\filter_date');

	$twig = new \Twig\Environment( $loader );
	$twig->addFilter( $date_filter );
	
	try {
		$output = $twig->render('jeero.html', $data );
	} catch( \Twig\Error\SyntaxError $e ) {
		return new \WP_Error( 'template', $e->getMessage() );
	}

	return $output;

}

/**
 * Twig 'date' filter that supports i18n dates.
 * 
 * @since	1.0
 * @param	string|int	$date
 * @param 	string		$format
 * @return	string
 */
function filter_date( $date, $format ) {
	
	if ( $format === null ) {
		$format = get_option( 'date_format' );
	}

	if ( is_numeric( $date ) && ( strtotime( $date ) === false || strlen( $date ) !== 8) ) {
		$timestamp = intval( $date );
	} else {
		$timestamp = strtotime( $date );
	}

	return date_i18n( $format, $timestamp );

}