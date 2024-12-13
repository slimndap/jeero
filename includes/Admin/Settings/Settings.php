<?php
/**
 * Handles Jeero settings.
 *
 * @since	1.30
 *
 */
namespace Jeero\Admin\Settings;

add_action( 'admin_init', __NAMESPACE__ . '\register_settings' );

/**
 * Outputs the Jeero settings admin page.
 *
 * @since 1.30
 */
function do_admin_page() {
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
        <form action="options.php" method="post">
            <?php
            // Output security fields for the registered setting
            settings_fields( 'jeero/settings/group' );
            // Output setting sections and their fields
            do_settings_sections( 'jeero/settings' );
            // Output save settings button
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

/**
 * Registers all Jeero settings.
 *
 * @since 1.30
 */
function register_settings() {
	
    // Register settings
    register_setting( 'jeero/settings/group', 'jeero/enable_custom_post_types' );
    register_setting( 'jeero/settings/group', 'jeero/enable_logs' );

    // Add settings sections
    add_settings_section(
        'jeero/settings/custom_post_types',
        __( 'Custom Post Types', 'jeero' ),
        __NAMESPACE__.'\custom_post_types_section',
        'jeero/settings'
    );

    add_settings_section(
        'jeero/settings/logs',
        __( 'Logs', 'jeero' ),
        __NAMESPACE__ . '\logs_section',
        'jeero/settings'
    );

	// Add settings fields
    add_settings_field(
        'jeero/enable_custom_post_types',
        __( 'Enable import to Custom Post Types', 'jeero' ),
        __NAMESPACE__ . '\custom_post_types_field',
        'jeero/settings',
        'jeero/settings/custom_post_types'
    );

    add_settings_field(
        'jeero/enable_logs',
        __( 'Enable logs', 'jeero' ),
        __NAMESPACE__ . '\show_logs_field',
        'jeero/settings',
        'jeero/settings/logs'
    );
    
}

/**
 * Outputs the 'Enable import to Custom Post Types' setting field.
 *
 * @since 1.30
 */
function custom_post_types_field() {
    $option = get_option( 'jeero/enable_custom_post_types' );
    ?>
    <input type="checkbox" name="jeero/enable_custom_post_types" value="1" <?php checked( 1, $option, true ); ?> />
    <?php
}

/**
 * Outputs the 'Enable logs' setting field.
 *
 * @since 1.30
 */
function show_logs_field() {
    $option = get_option( 'jeero/enable_logs' );
    ?>
    <input type="checkbox" name="jeero/enable_logs" value="1" <?php checked( 1, $option, true ); ?> />
    <?php
}

/**
 * Outputs the 'Custom Post Types' section instructions.
 *
 * @since 1.30
 */
function custom_post_types_section() {
	?><p><?php
		_e( 'Import events into a Custom Post Type instead of existing Calendar plugins.', 'jeero' );
		?><br><?php
		_e( 'Primarily aimed at developers who are able to create their own template functions.', 'jeero' );
	?></p><?php
}

/**
 * Outputs the 'Logs' section instructions.
 *
 * @since 1.30
 */
function logs_section() {
	?><p><?php
		_e( 'Keep logs from all import activity, which could help in solving any issues.', 'jeero' );
		?><br><?php
		_e( 'Adds a Logs submenu to the Jeero admin menu.', 'jeero' )
	?></p><?php
}
