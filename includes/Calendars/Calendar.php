<?php
/**
 * Jeero adds all Shows to his Calendar.
 */
namespace Jeero\Calendars;

class Calendar {
	
	public $slug = 'calendar';
	
	public $name = 'Calendar';
	
	function __construct() {}
	
	/**
	 * Applies a Twig template to a subscription.
	 * 
	 * @since 	1.10
	 * @since	1.16		Simplified function to just render a template.
	 *
	 * @param	string			$template		The Twig template.
	 * @param	string			$data			The event data.
	 * @param	Subscription		$subscription	The subscription.
	 * @return	string
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
		
		// Add custom fields to template variables.
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
	 * @since	1.16		Now returns <true> by default.
	 * @return	bool
	 */
	function is_active() {
		return true;
	}
	
	function get( $key ) {
		return $this->{ $key };
	}
	
	/**
	 * Gets the default template for a field in this calendar.
	 * 
	 * @since	1.16
	 * @param 	string	$field	The field.
	 * @return	string
	 */
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

	/**
	 * Gets the ref key for a calendar/theater combination.
	 * 
	 * @since	1.?
	 * @param 	string	$theater
	 * @return	string
	 */
	function get_ref_key( $theater ) {
		return sprintf( 'jeero/%s/%s/ref', $this->get( 'slug' ), $theater );
	}
	
	/**
	 * Gets a fully rendered Twig template for a field.
	 * 
	 * @since	1.16
	 * @since	1.18		@uses \Jeero\Calendars\Calendar::log().
	 *
	 * @param 	string 				$field			The field.
	 * @param 	array				$data			The event data.
	 * @param 	Jeero/Subscription	$subscription	The subscription.
	 * @return	string
	 */
	function get_rendered_template( $field, $data, $subscription ) {
		
		$template = $this->get_default_template( $field );
		
		$post_fields = $this->get_setting( 'import/post_fields', $subscription );

		// Check for custom template in settings.
		if ( !empty( $post_fields[ $field ] ) && !empty( $post_fields[ $field ][ 'template' ] ) ) {
			
			// Use custom template from settings. 
			$template = $post_fields[ $field ][ 'template' ];
			
		}
		
		$rendered_template = $this->apply_template(
			
			$template,
			$data, 
			$subscription 
			
		);

		if ( \is_wp_error( $rendered_template ) ) {
			
			$this->log( sprintf( 'Rendering %s template failed: %s.', $field, $rendered_template->get_error_message() ) );
			
			ob_start();
?>
<!-- 
	<?php printf( __( 'Rendering %s template failed:', 'jeero' ), $field ); ?>
	<?php echo $rendered_template->get_error_message(); ?>
-->
<?php
					
			$rendered_template = ob_get_clean();	
			
		} 

		return $rendered_template;
	}
	
	function get_setting( $key, $subscription, $default = '' ) {
		
		$key = sprintf( '%s/%s', $this->slug, $key );
		
		$setting = $subscription->get_setting( $key );
		
		if ( empty( $setting ) ) {
			return $default;
		}
		
		return $setting;
		
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
	
	/**
	 * Gets all fields that use templates for this calendar.
	 * 
	 * @since	1.16
	 * @return	array[]
	 */
	function get_template_fields() {
		return array();
	}

	/**
	 * Gets all template tags for a subscription.
	 * 
	 * @since	1.10
	 * @param	Subscription						$subscription	The subscription.
	 * @return	\Jeero\Templates\Fields\Field[]					All available template tags.
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
	 * @since	1.4		Added the subscription param.
	 * @since	1.18		@uses \Jeero\Calendars\Calendar::log().
	 */
	function import( $result, $data, $raw, $theater, $subscription ) {
		
		$this->log( sprintf( 'Import of %s item started.', $theater ) );

		$result = $this->process_data( $result, $data, $raw, $theater, $subscription );
		
		if ( \is_wp_error( $result ) ) {
			$this->log( sprintf( 'Import of %s item failed: %s.', $theater, $result->get_error_message() ) );
			return;
		}
		
		$this->log( sprintf( 'Import of %s item successful.', $theater ) );

		return $result;
		
	}
	
	function localize_timestamp( $timestamp ) {
		
		return $timestamp + get_option( 'gmt_offset' ) * HOUR_IN_SECONDS;
		
	}
	
	/**
	 * Logs a message for this Calendar to the Jeero log.
	 * 
	 * @since	1.18
	 * @param 	string	$message
	 * @return 	void
	 */
	function log( $message ) {
		
		$message = sprintf( '[%s] %s', $this->get( 'name' ), $message );
		\Jeero\Logs\log( $message );
		
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