<?php
/**
 * Jeero adds all Shows to his Calendar.
 */
namespace Jeero\Calendars;

class Calendar {
	
	public $slug = 'calendar';
	
	public $name = 'Calendar';
	
	function __construct() {
		
	}
	
	/**
	 * Applies a Twig template to a subscription.
	 * 
	 * @since 1.10
	 * @param	string			$context		The name of the template.
	 * @param	string			$data			The event data.
	 * @param	string			$default		The default template.
	 * @param	Subscription		$subscription	The subscription.
	 * @return void
	 */
	function apply_template( $context, $data, $default, $subscription ) {
		
		// Try to load template from subscription settings.
		$template = $this->get_setting( 'import/template/'.$context, $subscription );

		// Use default template if no/empty template found in settings.
		if ( empty( trim( $template ) ) ) {
			$template = $default;
		}
		
		// Prepare core template variables.	
		$template_data = array(
			'title' => $data[ 'production' ]	[ 'title' ],
			'description' => $data[ 'production' ][ 'description' ],
			'start' => $data[ 'start' ],
			'end' => $data[ 'end' ],
			'venue' => $data[ 'venue' ],
			'categories' => $data[ 'production' ][ 'categories' ],
			'tickets_url' => $data[ 'tickets_url' ],
			'status' => $data[ 'status' ],
			'prices' => $data[ 'prices' ],
		);
		
		// Add custom fields to temlpate variables.
		if ( !empty( $data[ 'custom' ] ) ) {
			$template_data = array_merge( $template_data, $data[ 'custom' ] );
		}
		
		// Render template.
		$output = \Jeero\Templates\render( $template, $template_data );
		
		if ( \is_wp_error( $output ) ) {
			
			error_log( sprintf( '[%s] Rendering template for %s field failed: %s.', $this->get( 'name' ), $context, $output->get_error_message() ) );
			
			ob_start();
?>
<!-- 
	<?php printf( __( 'Rendering template for %s field failed.', 'jeero' ), $this->get( 'name' ) ); ?>
	<?php echo $output->get_error_message(); ?>
-->
<?php
					
			return ob_get_clean();			
		} 
		
		return $output;		
		
	}
		
	/**
	 * Checks if this calendar is active.
	 * 
	 * @since	1.15
	 * @return	bool
	 */
	function is_active() {
		return false;
	}
	
	/**
	 * Gets the default Twig template for the event title field.
	 * 
	 * @since	1.10
	 * @return	string
	 */
	function get_default_title_template() {
		return '{{ title }}';
	}
	
	/**
	 * Gets the default Twig template for the event content field.
	 * 
	 * @since	1.10
	 * @return	string
	 */
	function get_default_content_template() {
		return '{{ description|raw }}';
	}
	
	/**
	 * Gets all settings fields for this calendar.
	 * 
	 * @since	1.4
	 * @since	1.5		Added a dedicated tab and activation checbox for each calendar. 
	 * @since	1.10		Added the $subscription param.
	 *					Needed for calendar that @uses Calendar::get_custom_fields_fields() to support custom fields.
	 *
	 * @param	Subscription		The subscription.
	 * @return	array
	 */
	function get_fields( $subscription ) {	
		return array(
			
			array(
				'name' => $this->slug,
				'type' => 'tab',
				'label' => $this->name,
			),
			array(
				'name' => 'calendar',
				'label' => __( 'Enable import', 'jeero' ),
				'type' => 'checkbox',
				'choices' => array(
					$this->slug => sprintf( __( 'Enable %s import', 'jeero' ), $this->name ),
				),
			),
			
		);
	}
	
	/**
	 * Gets (optional) import update setting fields.
	 * 
	 * @since	1.4
	 * @since	1.6	Added category field.
	 *
	 * @return	array
	 */
	function get_import_update_fields() {
		
		$fields = array();

		$import_choices = array(
			'once' => __( 'on first import', 'jeero' ),
			'always' => __( 'on every import', 'jeero' ),
		);
		
		$import_fields = array(
			'title' => __( 'event title', 'jeero' ),
			'description' => __( 'event description', 'jeero' ),
			'image' => __( 'event image', 'jeero' ),
			'categories' => __( 'event categories', 'jeero' ),
		);
		
		foreach( $import_fields as $name => $label ) {
			$fields[] = array(
				'name' => sprintf( '%s/import/update/%s', $this->slug, $name ),
				'label' => sprintf( __( 'Update %s', 'jeero' ), $label ),
				'type' => 'select',
				'choices' => $import_choices,
			);
		}
				
		return $fields;		
	}
	
	/**
	 * Gets (optional) import status setting field.
	 * 
	 * @since	1.4
	 * @return	array
	 */
	function get_import_status_fields() {

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
	 * Gets all template fields for a subscription.
	 * 
	 * @since	1.10
	 * @param	Subscription						$subscription	The subscription.
	 * @return	\Jeero\Templates\Fields\Field[]					All available core template fields.
	 */
	function get_template_fields( $subscription ) {
		
		$template_field_args = array(
			array(
				'name' => 'title',
				'type' => 'text',
				'description' => __( 'Event title', 'jeero' ),
			),	
			array(
				'name' => 'description',
				'type' => 'text',
				'description' => __( 'Event description', 'jeero' ),
			),	
			array(
				'name' => 'start',
				'type' => 'text',
				'description' => __( 'Start time', 'jeero' ),
			),	
			array(
				'name' => 'end',
				'type' => 'text',
				'description' => __( 'End time', 'jeero' ),
			),	
			array(
				'name' => 'tickets_url',
				'type' => 'text',
				'description' => __( 'Tickets URL', 'jeero' ),
			),	
			array(
				'name' => 'status',
				'type' => 'text',
				'description' => __( 'Current status of event. Possible values are \'onsale\', \'cancelled\', \'hidden\' and \'soldout\'.', 'jeero' ),
			),	
			array(
				'name' => 'venue',
				'type' => 'group',
				'label' => __( 'Venue', 'jeero' ),
				'description' => __( 'Venue', 'jeero' ),
				'sub_fields' => array(
					array(
						'name' => 'title',
						'type' => 'text',
						'label' => __( 'Title', 'jeero' ),
						'description' => __( 'Venue title', 'jeero' ),
					),
					array(
						'name' => 'city',
						'type' => 'text',
						'label' => __( 'City', 'jeero' ),
						'description' => __( 'Venue city', 'jeero' ),
					),
				)
			),	
			array(
				'name' => 'categories',
				'type' => 'select',
				'label' => __( 'Categories', 'jeero' ),
				'description' => __( 'Categories', 'jeero' ),
				'item' => array(
					'name' => 'category',
					'type' => 'text',	
				),
			),	
			array(
				'name' => 'prices',
				'type' => 'select',
				'label' => __( 'Prices', 'jeero' ),
				'description' => __( 'Prices', 'jeero' ),
				'item' => array(
					'name' => 'price',
					'type' => 'group',
					'description' => __( 'Price', 'jeero' ),	
					'label' => __( 'Price', 'jeero' ),	
					'sub_fields' => array(
						array(
							'name' => 'title',
							'type' => 'text',
							'label' => __( 'Title', 'jeero' ),
							'description' => 'Price title',	
						),
						array(
							'name' => 'amount',
							'type' => 'text',
							'label' => __( 'Amount', 'jeero' ),
							'description' => 'Price amount',	
						),
					),
				),
			),	
		);
		
		if ( !empty( $subscription->get( 'theater' )[ 'custom_fields' ] ) ) {
			$template_field_args = array_merge( $template_field_args, $subscription->get( 'theater' )[ 'custom_fields' ] );		
		}

		$template_fields = array();
		
		foreach( $template_field_args as $args ) {
			$template_fields[] = \Jeero\Templates\Fields\get_field_from_config( $args );
		}
		
		
		return $template_fields;
				
	}
	
	/**
	 * Get the rendered title value for an event.
	 * 
	 * @since	1.10
	 * @param 	array			$data	The event data.
	 * @param	Subscription		$subscription	The subscription.
	 * @return	string
	 */
	function get_title_value( $data, $subscription ) {
		
		return $this->apply_template( 
			'title', 
			$data, 
			$this->get_default_title_template(), 
			$subscription 
		);
		
	}
	
	/**
	 * Get the rendered content for an event.
	 * 
	 * @since	1.10
	 * @param 	array			$data			The event data.
	 * @param	Subscription		$subscription	The subscription.
	 * @return	string
	 */
	function get_content_value( $data, $subscription ) {
		
		return $this->apply_template( 
			'content', 
			$data, 
			$this->get_default_content_template(), 
			$subscription 
		);
		
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
	function get_custom_fields_fields( $subscription ) {
		
		ob_start();
		
		?><h2><?php _e( 'Custom templates', 'jeero' ); ?></h2>
		<p><?php
			printf( __( 'You can use <a href="%s" target="_blank">Twig</a> templates to customise the content of events.', 'jeero' ), 'https://twig.symfony.com/doc/3.x/templates.html' ); 
		?></p>
		<p><?php
			_e( 'The following variables are available in your templates', 'jeero' ); ?>:</p>
		<dl><?php
		
		foreach( $this->get_template_fields( $subscription ) as $field ) {
			
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
		
		$fields = array(
			array(
				'name' => 'template_instructions',
				'label' => $instructions,
				'type' => 'message',
			),
			array(
				'name' => sprintf( '%s/import/template/title', $this->slug ),
				'label' => __( 'Event title', 'jeero' ),
				'type' => 'template',
				'default' => $this->get_default_title_template(),
			),
			array(
				'name' => sprintf( '%s/import/template/content', $this->slug ),
				'label' => __( 'Event content', 'jeero' ),
				'type' => 'template',
				'default' => $this->get_default_content_template(),
			),
			array( 
				'name' => sprintf( '%s/import/template/custom_fields', $this->slug ),
				'label' => __( 'Custom fields', 'jeero' ),
				'type' => 'custom_fields'
			),
		);
		
		return $fields;
	}
	
	function get_setting( $key, $subscription, $default = '' ) {
		
		$settings = $subscription->get( 'settings' );
		
		$key = sprintf( '%s/%s', $this->slug, $key );
		
		if ( empty( $settings[ $key ] ) ) {
			return $default;
		}
		
		return $settings[ $key ];
		
	}
	
	function get_ref_key( $theater ) {
		return sprintf( 'jeero/%s/%s/ref', $this->get( 'slug' ), $theater );
	}
	
	function get( $key ) {
		return $this->{ $key };
	}
	
	/**
	 * Imports the data from an event in the inbox.
	 * 
	 * @since 	1.?
	 * @since	1.4	Added the subscription param.
	 */
	function import( $result, $data, $raw, $theater, $subscription ) {
		
		error_log( sprintf( '[%s] Import of %s item started.', $this->get( 'name' ), $theater ) );

		$result = $this->process_data( $result, $data, $raw, $theater, $subscription );
		
		if ( \is_wp_error( $result ) ) {
			error_log( sprintf( '[%s] Import of %s item failed: %s.', $this->get( 'name' ), $theater, $result->get_error_message() ) );
			return;
		}
		
		error_log( sprintf( '[%s] Import of %s item successful.', $this->get( 'name' ), $theater ) );

		return $result;
		
	}
	
	function localize_timestamp( $timestamp ) {
		
		return $timestamp + get_option( 'gmt_offset' ) * HOUR_IN_SECONDS;
		
	}
	
	/**
	 * Processes the data from an event in the inbox.
	 * 
	 * @since 	1.?
	 * @since	1.4	Added the subscription param.
	 */
	function process_data( $result, $data, $raw, $theater, $subscription ) {

		if ( empty( $data[ 'ref' ] ) ) {			
			return new \WP_Error( 'jeero/import', 'Ref identifier is missing' );
		}
		
		return $result;
		
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
					$custom_field[ 'name' ], 
					$data, 
					$custom_field[ 'template' ], 
					$subscription 
				) );
				
			}
			
		}
				
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