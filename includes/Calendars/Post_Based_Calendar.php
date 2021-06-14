<?php
namespace Jeero\Calendars;

use Jeero\Helpers\Images as Images;

abstract class Post_Based_Calendar extends Calendar {

	protected $post_type = false;
	
	protected $categories_taxonomy = false;

	function __construct() {
		
		parent::__construct();

	}

	function get_setting_field_categories() {
		
		$fields = array( 
			array(
				'name' => sprintf( '%s/import/category', $this->slug ),
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
	
	
	function get_categories_taxonomy( $subscription ) {
		return $this->categories_taxonomy;
	}
	
	function get_event_by_ref( $ref, $theater ) {
		
		error_log( sprintf( '[%s] Looking for existing %s item %s.', $this->get( 'name' ), $theater, $ref ) );
		
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
	
	function get_setting_fields( $subscription ) {
		
		$fields = parent::get_setting_fields( $subscription );
		
		$fields = array_merge( $fields, $this->get_setting_field_import_status() );
		$fields = array_merge( $fields, $this->get_setting_field_categories() );
		$fields = array_merge( $fields, $this->get_setting_field_custom_fields( $subscription ) );
		
		return $fields;
		
	}

	function get_template_fields() {
		
		$template_fields = parent::get_template_fields();
		
		$template_fields = array_merge( $template_fields, $this->get_post_fields() );

		return $template_fields;
		
	}

	/**
	 * Gets (optional) import status setting field.
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
		
	function get_post_id_by_title( $title, $post_type ) {
		
		$post_id = \wp_cache_get( $title, 'jeero/'.$post_type );

		if ( false === $post_id ) {
		
			$post = get_page_by_title( $title, OBJECT, $post_type );
			
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
	
	function get_post_ref( $data ) {
		return $data[ 'ref' ];
	}
	
	function get_post_type() {
		return $this->post_type;
	}
	
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

			error_log( sprintf( '[%s] Updating %s event %s / %d.', $this->name, $theater, $ref, $post_id ) );

			$post_args[ 'ID' ] = $post_id;

			if ( 'always' == $this->get_setting( 'import/update/title', $subscription, 'once' ) ) {

				$post_args[ 'post_title' ] = $this->get_rendered_template( 'title', $data, $subscription );
			}
			
			if ( 'always' == $this->get_setting( 'import/update/description', $subscription, 'once' ) ) {
				$post_args[ 'post_content' ] = $this->get_rendered_template( 'content', $data, $subscription );
			}
			
			if ( 'always' == $this->get_setting( 'import/update/excerpt', $subscription, 'once' ) ) {
				$post_args[ 'post_excerpt' ] = $this->get_rendered_template( 'excerpt', $data, $subscription );
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
				$thumbnail_id = Images\update_featured_image_from_url( 
					$post_id,
					$data[ 'production' ][ 'img' ]
				);
			}
			
		} else {

			error_log( sprintf( '[%s] Creating %s event %s.', $this->name, $theater, $ref ) );	

			$post_args[ 'post_title' ] = $this->get_rendered_template( 'title', $data, $subscription );
			$post_args[ 'post_content' ] = $this->get_rendered_template( 'content', $data, $subscription );
			$post_args[ 'post_excerpt' ] = $this->get_rendered_template( 'excerpt', $data, $subscription );
			$post_args[ 'post_status' ] = $this->get_setting( 'import/status', $subscription, 'draft' );
			
			$post_id = $this->insert_post( $post_args );

			if ( !empty( $data[ 'production' ][ 'img' ] ) ) {
				$thumbnail_id = Images\update_featured_image_from_url( 
					$post_id,
					$data[ 'production' ][ 'img' ]
				);
			}

			if ( !empty( $data[ 'production' ][ 'categories' ] ) ) {
				\wp_set_object_terms( $post_id, $data[ 'production' ][ 'categories' ], $this->get_categories_taxonomy( $subscription ), false  );
			}

			\add_post_meta( $post_id, $this->get_ref_key( $theater ), $ref, true );
			
		}

		$this->update_custom_fields( $post_id, $data, $subscription );

		\update_post_meta( $post_id, 'jeero/import/post/last_update', current_time( 'timestamp' ) );
		
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
	
	function update_featured_image( $post_id, $data ) {

		if ( empty( $data[ 'production' ][ 'img' ] ) ) {
			return false;
		}
		
		$thumbnail_id = Images\update_featured_image_from_url( 
			$post_id,
			$data[ 'production' ][ 'img' ]
		);
		
		if ( \is_wp_error( $thumbnail_id ) ) {
			error_log( sprintf( 'Updating thumbnail for event %d failed: %s', $post_id, $thumbnail_id->get_error_message() ) );
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
