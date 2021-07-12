<?php
namespace Jeero\Calendars;

// Register new calendar.
register_calendar( __NAMESPACE__.'\\Foyer' );

/**
 * Very_Simple_Event_List class.
 *
 * Adds support for the Very Simple Event List plugin.
 * @see: https://wordpress.org/plugins/very-simple-event-list/
 *
 * @since	1.2
 * 
 * @extends Calendar
 */
class Foyer extends Post_Based_Calendar {

	function __construct() {
		
		$this->slug = 'Foyer';
		$this->name = 'Foyer';
		$this->post_type = 'foyer_slide';
		
		parent::__construct();
		
	}
	
	function get_post_fields() {
		
		$post_fields = parent::get_post_fields();
		
		$new_posts_fields = array();
		
		foreach( $post_fields as $post_field ) {
			
			if ( 'title' == $post_field[ 'name' ] ) {
				$post_field[ 'title' ] = __( 'Post title', 'jeero' );
			}
			
			if ( 'content' == $post_field[ 'name' ] ) {
				continue;
			}
			
			if ( 'excerpt' == $post_field[ 'name' ] ) {
				continue;
			}
			
			$new_post_fields[] = $post_field;
		}
		
		$new_post_fields[] = array(
			'name' => 'slide_text_pretitle',
			'title' => sprintf( __( 'Slide %s', 'jeero' ), __( 'Pre-title', 'foyer' ) ),
			'template' => '{{ categories|join( \' / \' ) }}',
		);

		$new_post_fields[] = array(
			'name' => 'slide_text_title',
			'title' => sprintf( __( 'Slide %s', 'jeero' ), __( 'Title', 'foyer' ) ),
			'template' => '{{ title }}',
		);

		$new_post_fields[] = array(
			'name' => 'slide_text_subtitle',
			'title' => sprintf( __( 'Slide %s', 'jeero' ), __( 'Subtitle', 'foyer' ) ),
			'template' => sprintf(
				/* translators: Date/venue summary on Foyer Slides, eg. March 21 in Main Hall */
				__( '%1$s in %2$s', 'jeero' ),
				sprintf( 
					'{{ start|date( \'%s\' ) }}',
					/* translators: Date summary format on Foyer Slides, see https://www.php.net/manual/datetime.format.php */
					__( 'F d', 'jeero' )
				),
				'{{ venue.title }}'
			),
		);

		$new_post_fields[] = array(
			'name' => 'slide_text_content',
			'title' => sprintf( __( 'Slide %s', 'jeero' ), __( 'Content', 'foyer' ) ),
			'template' => '',
		);

		$new_post_fields[] = array(
			'name' => 'slide_bg_image_image',
			'title' => sprintf( __( 'Slide %s', 'jeero' ), __( 'Background image', 'foyer' ) ),
			'template' => '{{ image }}',
		);

		$new_post_fields[] = array(
			'name' => 'slide_bg_video_video_url',
			'title' => sprintf( __( 'Slide %s', 'jeero' ), __( 'YouTube video URL', 'foyer' ) ),
			'template' => '',
		);

		return $new_post_fields;

	}

	function sanitize_youtube_url( $url ) {

		$shortUrlRegex = '/youtu.be\/([a-zA-Z0-9_-]+)\??/i';
		$longUrlRegex = '/youtube.com\/((?:embed)|(?:watch))((?:\?v\=)|(?:\/))([a-zA-Z0-9_-]+)/i';
	
	    if (preg_match($longUrlRegex, $url, $matches)) {
	        $youtube_id = $matches[count($matches) - 1];
	    }
	
	    if (preg_match($shortUrlRegex, $url, $matches)) {
	        $youtube_id = $matches[count($matches) - 1];
	    }
	    return 'https://youtu.be/' . $youtube_id ;

	}
	
	/**
	 * Checks if this calendar is active.
	 * 
	 * @since	1.15
	 * @return	bool
	 */
	function is_active() {
		return defined( 'FOYER_PLUGIN_VERSION' );
	}
	
	/**
	 * Processes event data from Inbox items.
	 * 
	 * @since	1.?
	 * @since	1.4	Added the subscription param.
	 *
	 * @param 	mixed 			$result
	 * @param 	array			$data		The structured data of the event.
	 * @param 	array			$raw		The raw data of the event.
	 * @param	string			$theater		The theater.
	 * @param	Subscription		$theater		The subscription.
	 * @return	int|WP_Error
	 */	 
	function process_data( $result, $data, $raw, $theater, $subscription ) {

		$result = parent::process_data( $result, $data, $raw, $theater, $subscription );
		
		if ( \is_wp_error( $result ) ) {			
			return $result;
		}
		
		$ref = $this->get_post_ref( $data );

		if ( $event_id = $this->get_event_by_ref( $ref, $theater ) ) {

			update_post_meta( $event_id, 'slide_format', 'text' );
			update_post_meta( $event_id, 'slide_text_pretitle', $this->get_rendered_template( 'slide_text_pretitle', $data, $subscription ) );
			update_post_meta( $event_id, 'slide_text_title', $this->get_rendered_template( 'slide_text_title', $data, $subscription ) );
			update_post_meta( $event_id, 'slide_text_subtitle', $this->get_rendered_template( 'slide_text_subtitle', $data, $subscription ) );
			update_post_meta( $event_id, 'slide_text_content', $this->get_rendered_template( 'slide_text_content', $data, $subscription ) );

			if ( !empty( $data[ 'production' ][ 'img' ] ) ) {

				$thumbnail_id = \Jeero\Helpers\Images\add_image_to_library( $data[ 'production' ][ 'img' ], $event_id );
				
				if ( is_wp_error( $thumbnail_id ) ) {
					error_log( $thumbnail_id->get_error_message() );
				} else {
					update_post_meta( $event_id, 'slide_background', 'image' );						
					update_post_meta( $event_id, 'slide_bg_image_image', $thumbnail_id );
				}
				
			}

			$video_url = $this->get_rendered_template( 'slide_bg_video_video_url', $data, $subscription );

			if ( !empty( $video_url ) ) {

				
				update_post_meta( $event_id, 'slide_background', 'video' );						
				update_post_meta( $event_id, 'slide_bg_video_video_url', $this->sanitize_youtube_url( $video_url ) );						
			}

		}
		
		return $event_id;
		
		
	}
	
}