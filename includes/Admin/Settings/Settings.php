<?php
namespace Jeero\Admin\Settings;

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

// Hook the settings initialization to admin_init
add_action( 'admin_init', __NAMESPACE__ . '\register_settings' );

function register_settings() {
	
    // Register settings
    register_setting( 'jeero/settings/group', 'jeero/enable_custom_post_types' );
    register_setting( 'jeero/settings/group', 'jeero/enable_logs' );

    // Add settings sections
    add_settings_section(
        'jeero/settings/custom_post_types', // Section ID
        'Custom Post Types',               // Section title
        __NAMESPACE__.'\custom_post_types_section',                              // Callback for description (none needed)
        'jeero/settings'                   // Page to display the section
    );

    add_settings_section(
        'jeero/settings/logs',
        'Logs',
        __NAMESPACE__ . '\logs_section',
        'jeero/settings'
    );

    // Add settings fields
    add_settings_field(
        'jeero/enable_custom_post_types',          // Field ID
        'Enable import to Custom Post Types',      // Field title
        __NAMESPACE__ . '\custom_post_types_field',// Callback function
        'jeero/settings',                          // Page to display the field
        'jeero/settings/custom_post_types'          // Section ID
    );

    add_settings_field(
        'jeero/enable_logs',
        __( 'Enable logs', 'jeero' ),
        __NAMESPACE__ . '\show_logs_field',
        'jeero/settings',
        'jeero/settings/logs'
    );
}

function custom_post_types_field() {
    $option = get_option( 'jeero/enable_custom_post_types' );
    ?>
    <input type="checkbox" name="jeero/enable_custom_post_types" value="1" <?php checked( 1, $option, true ); ?> />
    <?php
}

function show_logs_field() {
    $option = get_option( 'jeero/enable_logs' );
    ?>
    <input type="checkbox" name="jeero/enable_logs" value="1" <?php checked( 1, $option, true ); ?> />
    <?php
}

function custom_post_types_section() {
	?><p><?php
		_e( 'Enabling this setting will make it possible for Jeero to import events into a Custom Post Type instead of existing Calendar plugins.', 'jeero' );
		?><br><?php
		_e( 'This is primarily aimed at developers who are able to create their own template functions.', 'jeero' );
	?></p><?php
}

function logs_section() {
	?><p><?php
		_e( 'Jeero is able to keep logs from all import activity, which could help in solving any issues.', 'jeero' );
		?><br><?php
		_e( 'Enabling this will add a Logs submenu to the Jeero admin menu.', 'jeero' )
	?></p><?php
}
