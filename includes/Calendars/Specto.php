<?php
namespace Jeero\Calendars;

use Jeero\Helpers\Images as Images;

/**
 * Specto class.
 *
 * @since	1.0.3
 * 
 * @extends Calendar
 */
class Specto extends Calendar {

	function __construct() {
		
		$this->slug = 'Specto';
		$this->name = __( 'Specto', 'jeero' );
		
		parent::__construct();
		
	}
	
	function get_fw_options_for_event( $event_id ) {
		
		$fw_options = \get_post_meta( $event_id, 'fw_options', true );
		
		if (empty( $fw_options ) ) {

			$fw_options = array(
				'page-builder' => array(
					'json' => '[]',
					'builder_active' => true,
				),
			);

		}
		
		return $fw_options;
		
	}
	
	function get_fw_option( $option, $event_id ) {
		
		$options = $this->get_fw_options_for_event( $event_id );
		
		if ( empty( $options[ $option ] ) ) {
			
			return false;
			
		}
		
		return $options[ $option ];
		
	}
	
	function set_fw_option( $option, $value, $event_id ) {
		
		$options = $this->get_fw_options_for_event( $event_id );
		
		$options[ $option ] = $value;
		
		\update_post_meta( $event_id, 'fw_options', $options );
		
	}
	
	function get_showtimes( $event_id ) {
		
		$showtimes_all = \get_post_meta( $event_id, 'jeero/Specto/showtimes', false );
		
		$showtimes = array();
		
		foreach( $showtimes_all as $showtime ) {
			
			$week = date( 'W', strtotime( $showtime[ 'start' ] ) );
			
			if ( $week != date( 'W' ) ) {
				continue;
			}
			
			$showtimes[] = $showtime;
			
		}
		
		uasort( $showtimes, __NAMESPACE__.'\compare_showtimes' );
		
		return $showtimes;
	}
	
	function get_showtimes_javascript( $event_id ) {
		
		$showtimes = $this->get_showtimes( $event_id );
		
		ob_start();
		
		?><script>jQuery( function() { <?php
			
			for( $st = 0; $st < count( $showtimes ); $st++ ) {
				
				$showtime = $showtimes[ $st ];
				
				$time = date( get_option( 'time_format' ), strtotime( $showtime[ 'start' ] ) );
				
				printf( 
					"jQuery( 'ul.show-times>li .time' ).eq( %d ).replaceWith( '<a href=\\\"%s\\\" class=\\\"time\\\">%s</a>' );", 
					$st,
					$showtime[ 'tickets_url' ],
					$time
				);
				
			}
			
		?>} );</script><?php
		
		return ob_get_clean();
		
	}
	
	function update_showtime( $showtime, $event_id ) {
		
		$current_showtimes = $this->get_showtimes( $event_id );
		
		foreach( $current_showtimes as $current_showtime ) {
			
			if ( $showtime[ 'ref' ] ==  $current_showtime[ 'ref' ] ) {
				return;
			}
			
		}
		
		\add_post_meta( $event_id, 'jeero/Specto/showtimes', $showtime );
		
	}

	function get_event_by_ref( $ref, $theater ) {
		
		error_log( sprintf( '[%s] Looking for existing %s item %s.', $this->get( 'name' ), $theater, $ref ) );
		
		$args = array(
			'post_status' => 'any',
			'post_type' => 'movie',
			'meta_query' => array(
				array(
					'key' => $this->get_ref_key( $theater ),
					'value' => $ref,					
				),
			),
		);
		
		$events = get_posts( $args );
		
		if ( empty( $events ) ) {
			return false;
		}
		
		return $events[ 0 ]->ID;
		
	}
	
	function process_data( $result, $data, $raw, $theater ) {
		
		$result = parent::process_data( $result, $data, $raw, $theater );
		
		if ( \is_wp_error( $result ) ) {			
			return $result;
		}
		
		if ( !\post_type_exists( 'movie' ) ) {
			return $result;
		}
		
		if ( !empty( $data[ 'production' ][ 'ref' ] ) ) {
			$ref = $data[ 'production' ][ 'ref' ];
		} else {
			$ref = 'e'.$data[ 'ref' ];		
		}
		
		$event_start = $this->localize_timestamp( strtotime( $data[ 'start' ] ) );
		$event_end = $this->localize_timestamp( strtotime( $data[ 'end' ] ) );

		$args = array(
			'post_type' => 'movie',
			'post_status' => 'draft',
		);
		
		$description = '';
		if ( !empty( $data[ 'production' ][ 'description' ] ) ) {
			$description = $data[ 'production' ][ 'description' ];	
		}
		
		if ( $event_id = $this->get_event_by_ref( $ref, $theater ) ) {

			$args[ 'ID' ] = $event_id;
			$event_id = \wp_update_post( $args );

			error_log( sprintf( '[%s] Updating event %s / %d.', $this->name, $ref, $event_id ) );
			
		} else {
			
			error_log( sprintf( '[%s] Creating event %s.', $this->name, $ref ) );

			$args[ 'post_title' ] = $data[ 'production' ][ 'title' ];
			
			if ( !empty( $data[ 'production' ][ 'description' ] ) ) {
				$args[ 'post_content' ] = $description;			
			}
			
			$event_id = \wp_insert_post( $args );
			
			\add_post_meta( $event_id, $this->get_ref_key( $theater ), $ref );

		}
		
		$showtime = array(
			'ref' => $data[ 'ref' ],
			'start' => $data[ 'start' ],
			'end' => $data[ 'end' ],
			'tickets_url' => $data[ 'tickets_url' ],
		);
		
		$this->update_showtime( $showtime, $event_id );
		
		$this->update_fw_options( $data, $event_id );
		
		$this->update_page_builder( $data, $event_id );
		
		return $event_id;

		$page_builder = array (
			0 => 
			array (
			'type' => 'section',
			'atts' => 
			array (
			  'is_fullwidth' => false,
			  'background_color' => '',
			  'background_image' => 
			  array (
			    'type' => 'custom',
			    'custom' => '',
			    'predefined' => '',
			    'data' => 
			    array (
			      'icon' => '',
			      'css' => 
			      array (
			      ),
			    ),
			  ),
			  'video' => '',
			  'border' => '0px 0px 0px 0px',
			  'padding_top' => '75',
			  'padding_bottom' => '75',
			  'id' => '',
			),
			'_items' => 
			array (
			  0 => 
			  array (
			    'type' => 'column',
			    'width' => '2_3',
			    'atts' => 
			    array (
			      'padding' => '0 15px 0 15px',
			      'class' => '',
			    ),
			    '_items' => 
			    array (
			      0 => 
			      array (
			        'type' => 'simple',
			        'shortcode' => 'special_heading',
			        'atts' => 
			        array (
			          'title' => 'Synopsis',
			          'heading' => 'h2',
			          'align' => 'left',
			          'border' => 'yes',
			          'colour' => '#ec7532',
			        ),
			      ),
			      1 => 
			      array (
			        'type' => 'simple',
			        'shortcode' => 'movie_info',
			        'atts' => 
			        array (
			          'social' => 'yes',
			          'title' => 'The plot',
			          'content' => "$description",
			          'list' => 
			          array (
			            0 => 
			            array (
			              'item' => '<i>Director</i> John Doe',
			            ),
			            1 => 
			            array (
			              'item' => '<i>Starring</i> James Hewitt, Jess Richards',
			            ),
			            2 => 
			            array (
			              'item' => '<i>Released</i> 15 Nov, 2017',
			            ),
			            3 => 
			            array (
			              'item' => '<i>Running time</i> 90 mins',
			            ),
			          ),
			        ),
			      ),
			    ),
			  ),
			  1 => 
			  array (
			    'type' => 'column',
			    'width' => '1_3',
			    'atts' => 
			    array (
			      'padding' => '0 15px 0 15px',
			      'class' => '',
			    ),
			    '_items' => 
			    array (
			      0 => 
			      array (
			        'type' => 'simple',
			        'shortcode' => 'special_heading',
			        'atts' => 
			        array (
			          'title' => 'Tickets',
			          'heading' => 'h2',
			          'align' => 'left',
			          'border' => 'yes',
			          'colour' => '#ec7532',
			        ),
			      ),
			      1 => 
			      array (
			        'type' => 'simple',
			        'shortcode' => 'movie_times',
			        'atts' => 
			        array (
			        ),
			      ),
			    ),
			  ),
			),
			),
			1 => 
			array (
			'type' => 'section',
			'atts' => 
			array (
			  'is_fullwidth' => true,
			  'background_color' => '#101010',
			  'background_image' => 
			  array (
			    'type' => 'custom',
			    'custom' => '',
			    'predefined' => '',
			    'data' => 
			    array (
			      'icon' => '',
			      'css' => 
			      array (
			      ),
			    ),
			  ),
			  'video' => '',
			  'border' => '0px 0px 0px 0px',
			  'padding_top' => '0',
			  'padding_bottom' => '0',
			  'id' => '',
			),
			'_items' => 
			array (
			  0 => 
			  array (
			    'type' => 'column',
			    'width' => '1_1',
			    'atts' => 
			    array (
			      'padding' => '0 15px 0 15px',
			      'class' => '',
			    ),
			    '_items' => 
			    array (
			      0 => 
			      array (
			        'type' => 'simple',
			        'shortcode' => 'movie_gallery',
			        'atts' => 
			        array (
			          'images' => 
			          array (
			            0 => 
			            array (
			              'thumb' => 
			              array (
			                'attachment_id' => '74',
			                'url' => '//jeero.local/wp-content/uploads/2017/07/gallery-thumb-1.png',
			              ),
			              'image' => 
			              array (
			                'attachment_id' => '73',
			                'url' => '//jeero.local/wp-content/uploads/2017/07/gallery-slide-2.jpg',
			              ),
			              'url' => 'https://youtu.be/AntcyqJ6brc',
			            ),
			            1 => 
			            array (
			              'thumb' => 
			              array (
			                'attachment_id' => '76',
			                'url' => '//jeero.local/wp-content/uploads/2017/07/gallery-thumb-3.png',
			              ),
			              'image' => 
			              array (
			                'attachment_id' => '72',
			                'url' => '//jeero.local/wp-content/uploads/2017/07/gallery-slide-1.jpg',
			              ),
			              'url' => 'https://youtu.be/AntcyqJ6brc',
			            ),
			            2 => 
			            array (
			              'thumb' => 
			              array (
			                'attachment_id' => '77',
			                'url' => '//jeero.local/wp-content/uploads/2017/07/gallery-thumb-4.png',
			              ),
			              'image' => 
			              array (
			                'attachment_id' => '72',
			                'url' => '//jeero.local/wp-content/uploads/2017/07/gallery-slide-1.jpg',
			              ),
			              'url' => 'https://youtu.be/AntcyqJ6brc',
			            ),
			            3 => 
			            array (
			              'thumb' => 
			              array (
			                'attachment_id' => '74',
			                'url' => '//jeero.local/wp-content/uploads/2017/07/gallery-thumb-1.png',
			              ),
			              'image' => 
			              array (
			                'attachment_id' => '73',
			                'url' => '//jeero.local/wp-content/uploads/2017/07/gallery-slide-2.jpg',
			              ),
			              'url' => '',
			            ),
			            4 => 
			            array (
			              'thumb' => 
			              array (
			                'attachment_id' => '78',
			                'url' => '//jeero.local/wp-content/uploads/2017/07/gallery-thumb-5.png',
			              ),
			              'image' => 
			              array (
			                'attachment_id' => '73',
			                'url' => '//jeero.local/wp-content/uploads/2017/07/gallery-slide-2.jpg',
			              ),
			              'url' => 'https://youtu.be/AntcyqJ6brc',
			            ),
			            5 => 
			            array (
			              'thumb' => 
			              array (
			                'attachment_id' => '79',
			                'url' => '//jeero.local/wp-content/uploads/2017/07/gallery-thumb-6.png',
			              ),
			              'image' => 
			              array (
			                'attachment_id' => '73',
			                'url' => '//jeero.local/wp-content/uploads/2017/07/gallery-slide-2.jpg',
			              ),
			              'url' => '',
			            ),
			            6 => 
			            array (
			              'thumb' => 
			              array (
			                'attachment_id' => '79',
			                'url' => '//jeero.local/wp-content/uploads/2017/07/gallery-thumb-6.png',
			              ),
			              'image' => 
			              array (
			                'attachment_id' => '73',
			                'url' => '//jeero.local/wp-content/uploads/2017/07/gallery-slide-2.jpg',
			              ),
			              'url' => '',
			            ),
			          ),
			          'large' => '6',
			          'medium' => '4',
			          'small' => '3',
			          'xsmall' => '2',
			        ),
			      ),
			    ),
			  ),
			),
			),
		);
		
		\update_post_meta( $event_id, 'fw:opt:ext:pb:page-builder:json', json_encode( $page_builder, JSON_UNESCAPED_UNICODE ) );

		// Update in listings.

		
		return $event_id;
		
	}
	
	function update_fw_options( $data, $event_id ) {
		
		if ( !empty( $data[ 'production' ][ 'img' ] ) ) {
			
			$thumbnail_id = Images\update_featured_image_from_url( 
				$event_id,
				$data[ 'production' ][ 'img' ]
			);
		
			$this->set_fw_option( 'poster', array(
				'attachment_id' => $thumbnail_id,
				'url' => wp_get_attachment_url( $thumbnail_id ),
			), $event_id );

		}
			
		// Set duration.
		$event_start = $this->localize_timestamp( strtotime( $data[ 'start' ] ) );
		$event_end = $this->localize_timestamp( strtotime( $data[ 'end' ] ) );

		if ( $event_end > $event_start ) {

			$this->set_fw_option( 
				'runningTime', 
				sprintf( '%d mins', ( $event_end - $event_start ) / 60 ),
				$event_id 
			);
			
		}		

		$this->set_fw_option( 'rating', 0, $event_id );
		
		// Update times from showtimes meta field.		
		$showtimes = $this->get_showtimes( $event_id );
		
		$days = array(
			
			'monTime' => array(),
			'tueTime' => array(),
			'wedTime' => array(),
			'thuTime' => array(),
			'friTime' => array(),
			'satTime' => array(),
			'sunTime' => array(),
			
		);
		
		foreach( $showtimes as $showtime ) {

			$day = strtolower( date( 'D', strtotime( $showtime[ 'start' ] ) ) ).'Time';
			$time = strtolower( date( 'H:i', strtotime( $showtime[ 'start' ] ) ) );
			
			$days[ $day ][] = array(
				'time' => $time,
				'3d' => '',
			);
			
		}
		
		foreach( $days as $day => $times ) {

			$this->set_fw_option( 
				$day, 
				$times,
				$event_id 
			);
						
		}
		
	}
	
	function update_page_builder( $data, $event_id ) {

		$description = '';
		if ( !empty( $data[ 'production' ][ 'description' ] ) ) {
			$description = $data[ 'production' ][ 'description' ];	
		}

		// Set running time.
		$running_time = '';
		
		$event_start = $this->localize_timestamp( strtotime( $data[ 'start' ] ) );
		$event_end = $this->localize_timestamp( strtotime( $data[ 'end' ] ) );

		if ( $event_end > $event_start ) {
			$running_time = 	sprintf( '<i>Running time</i> %d mins', ( $event_end - $event_start ) / 60 );
		}		

		$page_builder = array(
			
			array( 
				'type' => 'section',
				'atts' => array(
					'border' => '0px 0px 0px 0px',
					'padding_top' => '75',
					'padding_bottom' => '75',					
				),
				'_items' => array(
					array(
					    'type' => 'column',
					    'width' => '2_3',
					    'atts' => array(
							'padding' => '0 15px 0 15px',
					    ),
						'_items' => array(
							array (
								'type' => 'simple',
								'shortcode' => 'special_heading',
								'atts' => array (
									'title' => 'Synopsis',
									'heading' => 'h2',
									'align' => 'left',
									'border' => 'yes',
									'colour' => '#ec7532',
								),
							),

							array (
								'type' => 'simple',
								'shortcode' => 'movie_info',
								'atts' => array (
									'social' => 'yes',
									'title' => 'The plot',
									'content' => "$description",
									'list' => array (
										array (
											'item' => $running_time,
										),
									),
								),
							),

						),
					),

					array (
						'type' => 'column',
						'width' => '1_3',
						'atts' => array (
							'padding' => '0 15px 0 15px',
						),
						'_items' => array (
							array (
								'type' => 'simple',
								'shortcode' => 'special_heading',
								'atts' => array (
									'title' => 'Tickets',
									'heading' => 'h2',
									'align' => 'left',
									'border' => 'yes',
									'colour' => '#ec7532',
								),
							),
							array (
								'type' => 'simple',
								'shortcode' => 'movie_times',
							),
							array(
								'type' => 'simple',
								'shortcode' => 'text_block',
								'atts' => array(
									'text' => $this->get_showtimes_javascript( $event_id ),
								),
							),
						),
					),

				),
			),
			
		);
		
		\update_post_meta( $event_id, 'fw:opt:ext:pb:page-builder:json', json_encode( $page_builder, JSON_UNESCAPED_UNICODE ) );

	}
	
}

function compare_showtimes( $a, $b ) {
	
	$start_a = strtotime( $a[ 'start' ] );
	$start_b = strtotime( $b[ 'start' ] );

    if ($start_a == $start_b) {
        return 0;
    }
    
    return ( $start_a > $start_b ) ? -1 : 1;	
    
}
