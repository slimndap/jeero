<?php
/**
 * Leaves footprints all over the place.
 *
 * @since	1.17
 *
 */
namespace Jeero\Footprint;

// If YOAST can use priority 1 then so can we!
add_action( 'wp_head', __NAMESPACE__.'\leave_singular_footprint', 1 );

add_action( 'add_meta_boxes', __NAMESPACE__.'\add_meta_box', 10, 2 );


/**
 * Adds a Jeero meta box to all event admin screens.
 * 
 * @since	1.17
 * @since	1.17.1	Only add metabox if post was actually imported by Jeero.
 *
 * @return	void
 */
function add_meta_box( $post_type, $post ) {
	
	$active_calendars = \Jeero\Calendars\get_active_calendars();

	foreach( $active_calendars as $calendar ) {

		if ( !$calendar->is_imported_post( $post->ID ) ) {
			continue;
		}		

		\add_meta_box( 
			'jeero_footprint', 
			__( 'Jeero', 'jeero' ), 
			__NAMESPACE__.'\do_meta_box', 
			$calendar->get_post_type(),
			'side',
			'low',
			array(
				'calendar' => $calendar,
			)
		);
		
	}	
	
}

/**
 * Gets a formatted date string for use in footprint messages.
 * 
 * @since	1.17
 * @param	int		$datetime
 * @return	string
 */
function get_date_string( $datetime ) {

	$date_string = __( '%1$s at %2$s' );
	/* translators: Publish box date format, see https://www.php.net/manual/datetime.format.php */
	$date_format = _x( 'M j, Y', 'publish box date format' );
	/* translators: Publish box time format, see https://www.php.net/manual/datetime.format.php */
	$time_format = _x( 'H:i', 'publish box time format' );

	return sprintf(
		$date_string,
		date_i18n( $date_format, $datetime ),
		date_i18n( $time_format, $datetime )
	);	
	
}

/**
 * Output a Jeero meta box.
 * 
 * @since	1.17
 * @return	void
 */
 function do_meta_box( $post, $args ) {
	
	?><div class="created"><?php
		_e( 'Imported: ', 'jeero' );
		?><b><?php
			echo get_date_string( strtotime( $post->post_date ) );
		?></b><?php
			
	?></div><?php

	?><div class="last_update"><?php
		_e( 'Last update: ', 'jeero' );
		
		?><b><?php
			echo get_date_string( $args[ 'args' ][ 'calendar' ]->get_last_update( $post->ID ) );
		?></b><?php
			
	?></div><?php

	$subscription_id = \get_post_meta( $post->ID, 'jeero/import/post/subscription', true );
	if ( !empty( $subscription_id ) && current_user_can( 'manage_options' ) ) {
		
		?><div class="actions">
			<div class="edit">
				<a href="<?php echo \Jeero\Admin\Subscriptions\get_admin_edit_url( $subscription_id ); ?>"><?php
					_e( 'Edit settings', 'jeero' );
				?></a>				
			</div>
		</div><?php
			
	}

}

/**
 * Adds a Jeero footprint to single event pages.
 * 
 * @since	1.17
 * @return	void
 */
function leave_singular_footprint() {
	
	$active_calendars = \Jeero\Calendars\get_active_calendars();
	
	foreach( $active_calendars as $calendar ) {
		
		if ( !$calendar->is_singular() ) {
			continue;
		}
		
		?>

<!-- <?php printf( 
	__( 'This event is imported by Jeero on %s. Learn more: %s', 'jeero' ),
	get_date_string( $calendar->get_last_update( get_the_id() ) ), 
	'https://jeero.ooo' 
); ?> -->
<meta name="generator" content="Jeero <?php echo \Jeero\VERSION; ?>" />

		<?php

		
	}
}
