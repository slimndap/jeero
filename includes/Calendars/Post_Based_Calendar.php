<?php
namespace Jeero\Calendars;

use Jeero\Helpers\Images as Images;
use Jeero\Logs\Stats as Stats;

/**
 * Abstract Post_Based_Calendar class.
 * 
 * @abstract
 * @since	1.16
 * @extends Jeero\Calendars\Calendar
 */
abstract class Post_Based_Calendar extends Calendar {

	/**
	 * The post type slug.
	 * @since	1.16
	 */
	protected $post_type = false;
	
	/**
	 * The category taxonomy slug.
	 * @since	1.16
	 */
	protected $categories_taxonomy = false;

	/**
	 * Gets the categories setting field.
	 * 
	 * @since	1.16
	 * @return	array()
	 */
	function get_setting_field_categories() {
		
		$fields = array( 
			array(
				'name' => sprintf( '%s/import/update/categories', $this->slug ),
				'label' => __( 'Update event categories', 'jeero' ),
				'type' => 'select',
				'choices' => array(
					'once' => __( 'on first import', 'jeero' ),
					'always' => __( 'on every import', 'jeero' ),
				),
			)
		);
		
		return $fields;		
		
	}

	/**
	 * Gets all custom field settings fields for a subscription.
	 * 
	 * @since	1.10
	 * @since	1.12		Added HTML escaping to template field instructions.
	 * @since	1.14		Added support for custom fields.	
	 *
	 * @param 	Subscription							$subscription	The subscription
	 * @return	\Jeero\Subscriptions\Fields\Field[]					All custom field settings fields for a subscription.
	 */
	function get_setting_field_custom_fields( $subscription ) {
		
		ob_start();
		
		?><h2><?php _e( 'Custom templates', 'jeero' ); ?></h2>
		<p><?php
			printf( __( 'You can use <a href="%s" target="_blank">Twig</a> templates to customise the content of events.', 'jeero' ), 'https://twig.symfony.com/doc/3.x/templates.html' ); 
		?></p>
		<p><?php
			_e( 'The following variables are available in your templates', 'jeero' ); ?>:</p>
		<dl><?php
		
		foreach( $this->get_template_tags( $subscription ) as $field ) {
			
			$variables = $field->get_variables();

			foreach( $variables as $variable ) {
				?><dt class="<?php echo implode( ' ', $field->get_css_classes() ); ?>">
					<code>{{ <?php
						echo $variable[ 'name' ]; 			
					?> }}</code>
				</dt>
				<dd><?php
					echo $variable[ 'description' ];
					
					if ( $variable[ 'example' ] ) {
						?><div class="jeero-template-example">
							<h4><?php _e( 'Example usage', 'jeero' ); ?>:</h4>
							<p><pre><?php
								echo esc_html( $variable[ 'example' ] );
							?></pre></p>
						</div><?php
					}
				?></dd><?php				
			}
		}
		
		?></dl><?php
		
		$instructions = ob_get_clean();
		
		$fields = array();
		
		$fields[] =	array( 
			'name' => sprintf( '%s/import/post_fields', $this->slug ),
			'label' => __( 'Post fields', 'jeero' ),
			'type' => 'post_fields',
			'post_fields' => $this->get_post_fields(),
		);
		
		$fields[] =	array( 
			'name' => sprintf( '%s/import/template/custom_fields', $this->slug ),
			'label' => __( 'Custom fields', 'jeero' ),
			'type' => 'custom_fields'
		);
		
		$fields[] = array(
			'name' => 'template_instructions',
			'label' => $instructions,
			'type' => 'message',
		);
		
		return $fields;
	}
	
	/**
	 * Gets the image setting field.
	 * 
	 * @since	1.16
	 * @return	array()
	 */
	function get_setting_field_image() {
		
		$fields = array( 
			array(
				'name' => sprintf( '%s/import/update/image', $this->slug ),
				'label' => __( 'Update event image', 'jeero' ),
				'type' => 'select',
				'choices' => array(
					'once' => __( 'on first import', 'jeero' ),
					'always' => __( 'on every import', 'jeero' ),
				),
			)
		);
		
		return $fields;		
	}
	
	/**
	 * Gets the categories taxonomy.
	 * 
	 * @since	1.16
	 * @return	string
	 */
	function get_categories_taxonomy( $subscription ) {
		return $this->categories_taxonomy;
	}
	
	/**
	 * Gets an event post by its ref and theater values.
	 * 
	 * @since	1.?
	 * @param 	string			$ref
	 * @param 	string 			$theater
	 * @return 	WP_POST|bool					The event post or <false> if not found.
	 */
	function get_event_by_ref( $ref, $theater ) {
		
		$this->log( sprintf( 'Looking for existing %s item %s.', $theater, $ref ) );
		
		$args = array(
			'post_type' => $this->get_post_type(),
			'post_status' => 'any',
			'meta_query' => array(
				array(
					'key' => $this->get_ref_key( $theater ),
					'value' => $ref,					
				),
			),
		);
		
		$posts = \get_posts( $args );

		if ( empty( $posts ) ) {
			return false;
		}
		
		return $posts[ 0 ]->ID;
		
	}
	
	/**
	 * Gets the last update timestamp of an imported post.
	 * 
	 * @since	1.17
	 * @param	int	$post_id
	 * @return	int
	 */
	function get_last_update( $post_id ) {
		
		$last_update = get_post_meta( $post_id, 'jeero/import/post/last_update', true );
		
		if ( empty( $last_update ) ) {
			return false;			
		}
		
		return $last_update;
		
	}
	
	/**
	 * Gets all setting fields for a subscription.
	 * 
	 * @since	1.16
	 * @param 	Jeero\Subscription	$subscription
	 * @return	array
	 */
	function get_setting_fields( $subscription ) {
		
		$fields = parent::get_setting_fields( $subscription );
		
		$fields = array_merge( $fields, $this->get_setting_field_import_status() );
		$fields = array_merge( $fields, $this->get_setting_field_categories() );
		$fields = array_merge( $fields, $this->get_setting_field_image() );
		$fields = array_merge( $fields, $this->get_setting_field_custom_fields( $subscription ) );
		
		return $fields;
		
	}

	/**
	 * Gets all fields that use templates for this calendar.
	 * 
	 * @since	1.16
	 * @return	array[]
	 */
	function get_template_fields() {
		
		$template_fields = parent::get_template_fields();
		
		$template_fields = array_merge( $template_fields, $this->get_post_fields() );

		return $template_fields;
		
	}

	/**
	 * Gets import status setting field.
	 * 
	 * @since	1.4
	 * @return	array
	 */
	function get_setting_field_import_status() {

		$fields = array( 
			array(
				'name' => sprintf( '%s/import/status', $this->slug ),
				'label' => __( 'Status for new events', 'jeero' ),
				'type' => 'select',
				'choices' => array(
					'draft' => __( 'Draft' ),
					'publish' => __( 'Publish' ),
				),
			)
		);
		
		return $fields;
		
	}
	
	/**
	 * Retrieves a page given its title.
	 * Replaces  get_page_by_title() from WP because it was deprecated in WP 6.2.
	 *
	 * @since 1.25
	 *
	 * @param 	string	$title
	 * @param 	string	$post_type
	 * @return	WP_Post|null
	 */
	 
	 function get_page_by_title( $title, $post_type ) {
		
		$pages = get_posts(
			array(
				'post_type'              => $post_type,
				'title'                  => $title,
				'post_status'            => 'all',
				'numberposts'            => 1,
				'update_post_term_cache' => false,
				'update_post_meta_cache' => false,           
				'orderby'                => 'post_date ID',
				'order'                  => 'ASC',
			)
		);
		
		if ( empty( $pages ) ) {
			return null;
		}
		
		return $pages[0];
		
	}
	
	/**
	 * Gets a post ID by a title.
	 *
	 * Creates a new post if no post is found.
	 * 
	 * @since	1.16
	 *			1.25		Use get_page_by_title() from class to avoid a deprecation warning. 
	 * @param 	string	$title
	 * @param 	string	$post_type
	 * @return	int
	 */
	function get_post_id_by_title( $title, $post_type ) {
		
		$post_id = \wp_cache_get( $title, 'jeero/'.$post_type );

		if ( false === $post_id ) {
		
			$post = $this->get_page_by_title( $title, $post_type );
			
			if ( !( $post ) ) {
				
				$args = array(
					'post_type' => $post_type,
					'post_title' => $title,
					'post_content' => '',
					'post_status' => 'publish',	
				);
				
				$post_id = \wp_insert_post( $args );
				
			} else {
				
				$post_id = $post->ID;
				
			}
			
			\wp_cache_set( $title, $post_id, 'jeero/'.$post_type );
			
		}
		
		return $post_id;			
	}
	
	/**
	 * Gets the ref value for an event.
	 * 
	 * @since	1.16
	 * @param	array	$data	The event data.
	 * @return	string
	 */
	function get_post_ref( $data ) {
		return $data[ 'ref' ];
	}
	
	/**
	 * Gets the post type for this calendar.
	 * 
	 * @since	1.16
	 * @return	string
	 */
	function get_post_type() {
		return $this->post_type;
	}
	
	/**
	 * Gets all post fields for this calendar.
	 * 
	 * @since	1.16
	 * @return	array
	 */
	function get_post_fields() {
		
		$post_fields = array();
		
		$post_fields[] = array(
			'name' => 'title',
			'title' => __( 'Event title', 'jeero' ),
			'template' => '{{ title }}',
		);
		
		
		$post_fields[] = array(
			'name' => 'content',
			'title' => __( 'Event content', 'jeero' ),
			'template' => '{{ description|raw }}',
		);

		$post_fields[] = array(
			'name' => 'excerpt',
			'title' => __( 'Event excerpt', 'jeero' ),
			'template' => '',
		);
		
		return $post_fields;
		
	}
	
	/**
	 * Inserts or updates a post without sanitizing post content for allowed HTML tags.
	 * 
	 * @since	1.12
	 * @param 	array 			$args	An array of elements that make up a post to update or insert.
	 * @return	int|WP_Error				The post ID on success. The value 0 or WP_Error on failure.
	 */
	function insert_post( $args ) {
		
		// Temporarily disable sanitizing allowed HTML tags.
		\remove_filter( 'content_save_pre', 'wp_filter_post_kses' );
		
		$result = \wp_insert_post( $args );
		
		// Re-enable sanitizing allowed HTML tags.
		\add_filter( 'content_save_pre', 'wp_filter_post_kses' );
		
		return $result;
	}
	
	/**
	 * Checks if a post is imported by Jeero.
	 * 
	 * @since	1.17.1
	 * @param	int $post_id	The post ID.
	 * @return	bool
	 */
	function is_imported_post( $post_id ) {

		$last_update = $this->get_last_update( $post_id );
		return !empty( $last_update );
		
	}
	
	/**
	 * Checks if a post is single post from this calendar.
	 * 
	 * @since	1.17
	 * @since	1.17.1	Now @uses Jeero\Calendars::is_imported_post().
	 *
	 * @param 	int 	$post_id (default: false)
	 * @return	bool
	 */
	function is_singular( $post_id = false ) {
		
		if ( !$post_id ) {
			$post_id = get_the_id();
		}
		
		if ( !is_singular( $this->get_post_type() ) ) {
			return false;
		}
		
		return $this->is_imported_post( $post_id );
		
	}
	
	/**
	 * Processes the data from an event in the inbox.
	 * 
	 * @since 	1.16
	 * @since	1.26		Always use structured images.
	 * @since	1.30.1	Track stats for updated and created events.
	 */
	function process_data( $result, $data, $raw, $theater, $subscription ) {

		$result = parent::process_data( $result, $data, $raw, $theater, $subscription );

		if ( \is_wp_error( $result ) ) {			
			return $result;
		}
		
		$ref = $this->get_post_ref( $data );

		$post_args = array(
			'post_type' => $this->get_post_type(),
		);

		if ( $post_id = $this->get_event_by_ref( $ref, $theater ) ) {

			$this->log( sprintf( 'Updating %s event %s / %d.', $theater, $ref, $post_id ) );

			$post_args[ 'ID' ] = $post_id;

			$post_fields = $this->get_setting( 'import/post_fields', $subscription );

			foreach ( array( 'title', 'content', 'excerpt' ) as $post_field ) {

				if ( 
					!empty( $post_fields[ $post_field ] ) &&
					!empty( $post_fields[ $post_field ][ 'update' ] ) &&
					'always' == $post_fields[ $post_field ][ 'update' ]
				) {
					$post_args[ 'post_'.$post_field ] = $this->get_rendered_template( $post_field, $data, $subscription );
				}
				
			}
			
			$this->update_post( $post_args );

			if ( 'always' == $this->get_setting( 'import/update/categories', $subscription, 'once' ) ) {
				if ( empty( $data[ 'production' ][ 'categories' ] ) ) {
					\wp_set_object_terms( $post_id, array(), $this->get_categories_taxonomy( $subscription ), false  );			
				} else {
					\wp_set_object_terms( $post_id, $data[ 'production' ][ 'categories' ], $this->get_categories_taxonomy( $subscription ), false  );
				}
			}

			if ( 
				'always' == $this->get_setting( 'import/update/image', $subscription, 'once' ) && 
				!empty( $data[ 'production' ][ 'img' ] )
			) {
				$structured_image = Images\get_structured_image( $data[ 'production' ][ 'img' ], $post_id );
				$thumbnail_id = Images\update_featured_image( $post_id,	$structured_image );
			}
			
			\update_post_meta( $post_id, 'jeero/import/post/last_update', current_time( 'timestamp' ) );
			
			Stats\cache_set(
				'events_updated', 
				Stats\cache_get( 'events_updated' ) + 1
			);
		
		} else {

			$this->log( sprintf( 'Creating %s event %s.', $theater, $ref ) );	

			$post_args[ 'post_title' ] = $this->get_rendered_template( 'title', $data, $subscription );
			$post_args[ 'post_content' ] = $this->get_rendered_template( 'content', $data, $subscription );
			$post_args[ 'post_excerpt' ] = $this->get_rendered_template( 'excerpt', $data, $subscription );
			$post_args[ 'post_status' ] = $this->get_setting( 'import/status', $subscription, 'draft' );

			$post_id = $this->insert_post( $post_args );

			if ( !empty( $data[ 'production' ][ 'img' ] ) ) {
				$structured_image = Images\get_structured_image( $data[ 'production' ][ 'img' ], $post_id );
				$thumbnail_id = Images\update_featured_image( $post_id,	$structured_image );
			}

			if ( !empty( $data[ 'production' ][ 'categories' ] ) ) {
				\wp_set_object_terms( $post_id, $data[ 'production' ][ 'categories' ], $this->get_categories_taxonomy( $subscription ), false  );
			}

			\add_post_meta( $post_id, $this->get_ref_key( $theater ), $ref, true );

			Stats\cache_set(
				'events_created', 
				Stats\cache_get( 'events_created' ) + 1
			);
		
		}

		$this->update_custom_fields( $post_id, $data, $subscription );
		
		\update_post_meta( $post_id, 'jeero/import/post/subscription', $subscription->ID );

		return $post_id;
		
	}

	/**
	 * Updates the custom fields of a post.
	 * 
	 * @since	1.14
	 * @param 	int					$post_id			The post ID.
	 * @param	array				$data			The structured event data.
	 * @param 	Subscription			$subscription	The subscriptions.
	 * @return 	void
	 */
	function update_custom_fields( $post_id, $data, $subscription ) {
		
		$custom_fields = $this->get_setting( 'import/template/custom_fields', $subscription, array() );

		if ( !empty( $custom_fields ) && is_array( $custom_fields ) ) {
			
			foreach( $custom_fields as $custom_field ) {
				
				\update_post_meta( $post_id, $custom_field[ 'name' ], $this->apply_template( 
					$custom_field[ 'template' ], 
					$data, 
					$subscription 
				) );
				
			}
			
		}
				
	}
	
	/**
	 * Updates the featured image of a post.
	 * 
	 * @since	1.16
	 * @param 	int					$post_id	The post ID.
	 * @param	array				$data		The event data.
	 * @return	int|bool|WP_Error				The featured image ID.
	 *											WP_Error if there is a problem.
	 *											<false> if no the event has no image.
	 */
	function update_featured_image( $post_id, $data ) {

		if ( empty( $data[ 'production' ][ 'img' ] ) ) {
			return false;
		}
		
		$thumbnail_id = Images\update_featured_image_from_url( 
			$post_id,
			$data[ 'production' ][ 'img' ]
		);
		
		if ( \is_wp_error( $thumbnail_id ) ) {
			$this->log( sprintf( 'Updating thumbnail for event %d failed: %s', $post_id, $thumbnail_id->get_error_message() ) );
		}		

		return $thumbnail_id;
		
	}
	
	/**
	 * Updates a post without sanitizing post content for allowed HTML tags.
	 * 
	 * @since	1.12
	 * @param 	array 			$args	An array of elements that make up a post to update or insert.
	 * @return	int|WP_Error				The post ID on success. The value 0 or WP_Error on failure.
	 */
	function update_post( $args ) {
		
		// Temporarily disable sanitizing allowed HTML tags.
		\remove_filter( 'content_save_pre', 'wp_filter_post_kses' );
		
		$result = \wp_update_post( $args );
		
		// Re-enable sanitizing allowed HTML tags.
		\add_filter( 'content_save_pre', 'wp_filter_post_kses' );
		
		return $result;		
	}

}
