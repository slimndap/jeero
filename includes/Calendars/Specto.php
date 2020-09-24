<?php
namespace Jeero\Calendars;

use Jeero\Helpers\Images as Images;

if ( 'Specto' == wp_get_theme() ) {

	// Replace data in all movie schedules with data imported by Jeero.
	add_filter( 'fw_shortcode_render_view:atts', __NAMESPACE__.'\Specto::set_movie_schedule', 10, 2 );
	
	// Replace movie times output with a custom version based on the dates imported by Jeero.
	add_action( 'init', function() {
		add_shortcode( 'movie_times', __NAMESPACE__.'\Specto::get_movie_times_html' );
	}, 99 );

}

/**
 * Specto class.
 *
 * Adds support for the Specto theme.
 * @see 1.envato.market/vRd3N
 *
 * @since	1.2
 * 
 * @extends Calendar
 */
class Specto extends Calendar {

	function __construct() {
		
		$this->slug = 'Specto';
		$this->name = __( 'Specto', 'jeero' );
		
		parent::__construct();
		
	}
	
	static function compare_showtimes( $a, $b ) {
		
		$start_a = strtotime( $a[ 'start' ] );
		$start_b = strtotime( $b[ 'start' ] );
	
	    if ($start_a == $start_b) {
	        return 0;
	    }
	    
	    return ( $start_a < $start_b ) ? -1 : 1;	
	    
	}

	static function get_movie_times_html() {
		
		if ( !is_singular( 'movie' ) ) {
			return;
		}
		
		ob_start();
		
		$showtimes = self::get_showtimes( get_the_id() );
		
		$showtimes_per_day = array();
		foreach( $showtimes as $showtime ) {
			$day = date( 'Y-m-d', strtotime( $showtime[ 'start' ] ) );
			if ( empty( $day ) ) {
				$showtimes_per_day[ $day ] = array();				
			}
			$showtimes_per_day[ $day ][] = $showtime;
			
		}
		
		?><ul class="show-times"><?php
			foreach( $showtimes_per_day as $day => $showtimes ) {
				
				$today = date( 'Y-m-d' ) == $day;
				
				?><li<?php if ( $today ) { ?> class="today"<?php } ?>>
					<i><?php 
						
						if ( $today ) {
							_e( 'Today', 'specto' );
						} else {
							echo date_i18n( 'D jS', strtotime( $day ) );
						}
						
					?></i> <?php
					foreach( $showtimes as $showtime ) {
						
						if ( !empty( $showtime[ 'tickets_url' ] ) ) {
							?><a href="<?php echo $showtime[ 'tickets_url' ]; ?>" class="time"><?php 
								echo date( get_option( 'time_format' ), strtotime( $showtime['start'] ) ); 
							?></a><?php								
						} else {
							?><span class="time"><?php 
								echo date( get_option( 'time_format' ), strtotime( $showtime['start'] ) ); 
							?></span><?php		
						}
					}
				?></li><?php
			}
		?></ul><?php
		return ob_get_clean();
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
	
	static function get_showtimes( $event_id ) {
	
		$showtimes_all = \get_post_meta( $event_id, 'jeero/Specto/showtimes', false );
		
		$showtimes = array();
		
		foreach( $showtimes_all as $showtime ) {
			
			$showtimes[] = $showtime;
			
		}
		
		uasort( $showtimes, __NAMESPACE__.'\Specto::compare_showtimes' );
		
		return $showtimes;
		
	}

	function set_fw_option( $option, $value, $event_id ) {
		
		$options = $this->get_fw_options_for_event( $event_id );
		
		$options[ $option ] = $value;
		
		\update_post_meta( $event_id, 'fw_options', $options );
		
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
		
		$args = array(
			'post_type' => 'movie',
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

			$args[ 'post_status' ] = 'draft';
			$args[ 'post_title' ] = $data[ 'production' ][ 'title' ];
			
			if ( !empty( $data[ 'production' ][ 'description' ] ) ) {
				$args[ 'post_content' ] = $description;			
			}
			
			$event_id = \wp_insert_post( $args );
			
			\add_post_meta( $event_id, $this->get_ref_key( $theater ), $ref );

			$this->update_page_builder( $data, $event_id );			

		}
		
		$showtime = array(
			'ref' => $data[ 'ref' ],
			'start' => $data[ 'start' ],
			'tickets_url' => $data[ 'tickets_url' ],
		);
		
		if ( !empty( $data[ 'end' ] ) ) {
			$showtimes[ 'end' ] = $data[ 'end' ];
		}
		
		$this->update_showtime( $showtime, $event_id );
		
		$this->update_fw_options( $data, $event_id );
		
		return $event_id;
		
	}
	
	static function set_movie_schedule( $atts, $shortcode ) {
		
		if ( 'movie_schedule' != $shortcode ) {
			return $atts;
		}
		
		$schedule = array(
			array( 
				'day' => 'Mon',
				'movies' => array(),
			),
			array( 
				'day' => 'Tue',
				'movies' => array(),
			),
			array( 
				'day' => 'Wed',
				'movies' => array(),
			),
			array( 
				'day' => 'Thu',
				'movies' => array(),
			),
			array( 
				'day' => 'Fri',
				'movies' => array(),
			),
			array( 
				'day' => 'Sat',
				'movies' => array(),
			),
			array( 
				'day' => 'Sun',
				'movies' => array(),
			),			 
		);
		
		$args = array(
			'post_type' => 'movie',
			'post_status' => 'publish',
			'posts_per_page' => 100,
		);
		$events = get_posts( $args );
		
		foreach( $events as $event ) {
			
			$showtimes = self::get_showtimes( $event->ID );
				
			foreach( $showtimes as $showtime ) {
	
				// Skip showtime if not in current week.
				if ( date( 'W' ) != date( 'W', strtotime( $showtime[ 'start' ] ) ) ) {
					continue;
				}
		
				$day = strtolower( date( 'N', strtotime( $showtime[ 'start' ] ) ) ) - 1;
				$atts[ 'schedule' ][ $day ][ 'movies' ][] = $event->ID;
				
			}
			
		}
	
		return $atts;
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
			
		$this->set_fw_option( 'background', array(
			'attachment_id' => '',
			'url' => '',
		), $event_id );

		// Set duration.
		if ( !empty( $data[ 'end' ] ) ) {
			
			$event_start = $this->localize_timestamp( strtotime( $data[ 'start' ] ) );
			$event_end = $this->localize_timestamp( strtotime( $data[ 'end' ] ) );
	
			if ( $event_end > $event_start ) {
	
				$this->set_fw_option( 
					'runningTime', 
					sprintf( '%d mins', ( $event_end - $event_start ) / 60 ),
					$event_id 
				);
				
			}		
			
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
			
			// Skip showtime if not in current week.
			if ( date( 'W' ) != date( 'W', strtotime( $showtime[ 'start' ] ) ) ) {
				continue;
			}

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
		if ( !empty( $data[ 'end' ] ) ) {
			$event_end = $this->localize_timestamp( strtotime( $data[ 'end' ] ) );		
			if ( $event_end > $event_start ) {
				$running_time = sprintf( '<i>Running time</i> %d mins', ( $event_end - $event_start ) / 60 );
			}		
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
									'content' => trim( json_encode( $description ), '"' ),
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
						),
					),

				),
			),
			
		);
		
		\update_post_meta( $event_id, 'fw:opt:ext:pb:page-builder:json', json_encode( $page_builder, JSON_UNESCAPED_UNICODE ) );

	}

}





