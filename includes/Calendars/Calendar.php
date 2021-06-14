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
	function apply_template( $template, $data, $subscription ) {
				
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
	
	function get( $key ) {
		return $this->{ $key };
	}
	
	function get_default_template( $field ) {
		
		$template_fields = $this->get_template_fields();
		
		foreach( $template_fields as $template_field ) {
			
			if ( $template_field[ 'name' ] !=  $field ) {
				continue;
			}
			
			return $template_field[ 'template' ];
			
		}
		
		return false;
		
	}

	function get_ref_key( $theater ) {
		return sprintf( 'jeero/%s/%s/ref', $this->get( 'slug' ), $theater );
	}
	
	function get_rendered_template( $template_name, $data, $subscription ) {
		
		$template = $this->get_default_template( $template_name );
		
		$post_fields = $this->get_setting( 'import/post_fields', $subscription );
		if ( !empty( $post_fields[ $template_name ] ) && !empty( $post_fields[ $template_name ][ 'template' ] ) ) {
			$template = $post_fields[ $template_name ][ 'template' ];
		}
		
		$rendered_template = $this->apply_template(
			
			$template,
			$data, 
			$subscription 
			
		);

		if ( \is_wp_error( $rendered_template ) ) {
			
			error_log( sprintf( '[%s] Rendering %s template failed: %s.', $this->get( 'name' ), $template_name, $rendered_template->get_error_message() ) );
			
			ob_start();
?>
<!-- 
	<?php printf( __( 'Rendering %s template failed:', 'jeero' ), $template_name ); ?>
	<?php echo $rendered_template->get_error_message(); ?>
-->
<?php
					
			$rendered_template = ob_get_clean();	
			
		} 

		return $rendered_template;
	}
	
	function get_setting( $key, $subscription, $default = '' ) {
		
		$settings = $subscription->get( 'settings' );
		
		$key = sprintf( '%s/%s', $this->slug, $key );
		
		if ( empty( $settings[ $key ] ) ) {
			return $default;
		}
		
		return $settings[ $key ];
		
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
	function get_setting_fields( $subscription ) {	
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
	
	function get_template_fields() {
		return array();
	}

	/**
	 * Gets all template fields for a subscription.
	 * 
	 * @since	1.10
	 * @param	Subscription						$subscription	The subscription.
	 * @return	\Jeero\Templates\Fields\Field[]					All available core template fields.
	 */
	function get_template_tags( $subscription ) {
		
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
				'description' => sprintf( 
					__( 'Current status of event. Possible values are %s, %s, %s and %s.', 'jeero' ),
					'<code>onsale</code>', '<code>cancelled</code>', '<code>hidden</code>', '<code>soldout</code>'
				),
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