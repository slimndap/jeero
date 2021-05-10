<?php
namespace Jeero\Templates;

function render( $template, $data ) {

	$loader = new \Twig\Loader\ArrayLoader( array(
	    'jeero.html' => $template,
	) );
	
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