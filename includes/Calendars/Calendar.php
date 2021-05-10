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
	
	function apply_template( $context, $data, $default, $subscription ) {
		
		$template = $this->get_setting( 'import/template/'.$context, $subscription );

		if ( empty( trim( $template ) ) ) {
			$template = $default;
		}
				
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
		
		if ( !empty( $data[ 'custom' ] ) ) {
			$template_data = array_merge( $template_data, $data[ 'custom' ] );
		}
		
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
	
	function get_default_title_template() {
		return '{{ title }}';
	}
	
	function get_default_content_template() {
		return '{{ description|raw }}';
	}
	
	/**
	 * Gets all fields for this calendar.
	 * 
	 * @since	1.4
	 * @since	1.5	Added a dedicated tab and activation checbox for each calendar. 
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
	
	function get_template_fields( $subscription ) {
		
		$template_field_args = array(
			array(
				'name' => 'title',
				'type' => 'text',
				'description' => 'Event title',
			),	
			array(
				'name' => 'description',
				'type' => 'text',
				'description' => 'Event description',
			),	
			array(
				'name' => 'start',
				'type' => 'text',
				'description' => 'Start time',
			),	
			array(
				'name' => 'end',
				'type' => 'text',
				'description' => 'End time',
			),	
			array(
				'name' => 'tickets_url',
				'type' => 'text',
				'description' => 'Tickets URL',
			),	
			array(
				'name' => 'status',
				'type' => 'text',
				'description' => 'Current status of event. Possible values are \'onsale\', \'cancelled\', \'hidden\' and \'soldout\'.',
			),	
			array(
				'name' => 'venue',
				'type' => 'group',
				'label' => __( 'Venue', 'jeero' ),
				'description' => 'Venue',
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
		
		$template_field_args = array_merge( $template_field_args, $subscription->get( 'theater' )[ 'custom_fields' ] );

		$template_fields = array();
		
		foreach( $template_field_args as $args ) {
			$template_fields[] = \Jeero\Templates\Fields\get_field_from_config( $args );
		}
		
		
		return $template_fields;
				
	}
	
	function get_custom_fields_fields( $subscription ) {
		
		ob_start();
		
		?><h2><?php _e( 'Custom templates', 'jeero' ); ?></h2>
		<p>You can use <a href="https://twig.symfony.com/doc/3.x/templates.html" target="_blank">Twig</a> templates to customise the content of events.</p>
		<p>The following variables are available in your templates:</p>
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
				'label' => __( 'Title of event', 'jeero' ),
				'type' => 'template',
				'instructions' => sprintf( 
					__( 'Leave empty to use the default template: <code>%s</code>.', 'jeero' ),
					$this->get_default_title_template()
				),
			),
			array(
				'name' => sprintf( '%s/import/template/content', $this->slug ),
				'label' => __( 'Main content of event', 'jeero' ),
				'type' => 'template',
				'instructions' => sprintf( 
					__( 'Leave empty to use the default template: <code>%s</code>.', 'jeero' ),
					$this->get_default_content_template()
				),
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
	
}