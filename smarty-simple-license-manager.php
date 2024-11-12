<?php
/**
 * Plugin Name:             SM - Simple License Manager
 * Plugin URI:              https://github.com/mnestorov/smarty-simple-license-manager
 * Description:             A plugin to manage licenses with custom post types, status management, and API keys.
 * Version:                 1.0.0
 * Author:                  Smarty Studio | Martin Nestorov
 * Author URI:              https://github.com/mnestorov
 * License:                 GPL-2.0+
 * License URI:             http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:             smarty-simple-license-manager
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
	die;
}

// Register License custom post type
function lm_register_license_post_type() {
    register_post_type('license', array(
        'labels' => array(
            'name' => 'Licenses',
            'singular_name' => 'License'
        ),
        'public' => true,
        'has_archive' => true,
        'supports' => array('title', 'editor'),
        'show_in_rest' => true,
    ));
}
add_action('init', 'lm_register_license_post_type');

// Add meta boxes for license details
function lm_add_license_meta_boxes() {
    add_meta_box('license_details', 'License Details', 'lm_license_details_callback', 'license', 'normal', 'default');
}
add_action('add_meta_boxes', 'lm_add_license_meta_boxes');

function lm_license_details_callback($post) {
    // Retrieve existing values from the post meta, if available
    $license_key = get_post_meta($post->ID, '_license_key', true);
    $user_email = get_post_meta($post->ID, '_user_email', true);
    $purchase_date = get_post_meta($post->ID, '_purchase_date', true);
    $expiration_date = get_post_meta($post->ID, '_expiration_date', true);
    $status = get_post_meta($post->ID, '_status', true);

    // Output the fields with existing values
    echo '<label>License Key:</label><input type="text" name="license_key" value="'.esc_attr($license_key).'" /><br>';
    echo '<label>User Email:</label><input type="email" name="user_email" value="'.esc_attr($user_email).'" /><br>';
    echo '<label>Purchase Date:</label><input type="date" name="purchase_date" value="'.esc_attr($purchase_date).'" /><br>';
    echo '<label>Expiration Date:</label><input type="date" name="expiration_date" value="'.esc_attr($expiration_date).'" /><br>';

    echo '<label>Status:</label><select name="status">';
    foreach (array('active', 'inactive', 'expired') as $option) {
        $selected = $status === $option ? 'selected' : '';
        echo '<option value="'.$option.'" '.$selected.'>'.ucfirst($option).'</option>';
    }
    echo '</select>';

    // Add nonce field for security
    echo wp_nonce_field('lm_save_license_meta', 'lm_license_nonce');
}

// Save license meta data with nonce verification
function lm_save_license_meta($post_id, $post) {
    if ($post->post_type === 'license') {
        // Verify nonce for security
        if (!isset($_POST['lm_license_nonce']) || !wp_verify_nonce($_POST['lm_license_nonce'], 'lm_save_license_meta')) {
            return $post_id;
        }

        // Generate a unique license key if none exists
        if (empty($_POST['license_key'])) {
            $license_key = strtoupper(wp_generate_password(16, false, false));
            update_post_meta($post_id, '_license_key', $license_key);
        } else {
            update_post_meta($post_id, '_license_key', sanitize_text_field($_POST['license_key']));
        }
        
        update_post_meta($post_id, '_user_email', sanitize_email($_POST['user_email']));
        update_post_meta($post_id, '_purchase_date', sanitize_text_field($_POST['purchase_date']));
        update_post_meta($post_id, '_expiration_date', sanitize_text_field($_POST['expiration_date']));
        update_post_meta($post_id, '_status', sanitize_text_field($_POST['status']));
    }
}

// Set up daily cron job for checking expired licenses
function lm_schedule_cron_job() {
    if (!wp_next_scheduled('lm_license_cron_job')) {
        wp_schedule_event(time(), 'daily', 'lm_license_cron_job');
    }
}
add_action('wp', 'lm_schedule_cron_job');

// Cron job function to check expired licenses
function lm_check_expired_licenses() {
    $licenses = get_posts(array('post_type' => 'license', 'posts_per_page' => -1));
    foreach ($licenses as $license) {
        $expiration_date = get_post_meta($license->ID, '_expiration_date', true);
        if (strtotime($expiration_date) < time()) {
            update_post_meta($license->ID, '_status', 'expired');
        }
    }
}
add_action('lm_license_cron_job', 'lm_check_expired_licenses');

// Create settings page for API key generation
function lm_license_manager_settings_page() {
    add_options_page('License Manager Settings', 'License Manager', 'manage_options', 'license_manager', 'lm_license_manager_settings_page_callback');
}
add_action('admin_menu', 'lm_license_manager_settings_page');

function lm_license_manager_settings_page_callback() {
    ?>
    <div class="wrap">
        <h1>License Manager Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('license_manager_settings');
            do_settings_sections('license_manager_settings');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

function lm_license_manager_register_settings() {
    register_setting('license_manager_settings', 'license_manager_api_key');
    add_settings_section('license_manager_section', 'API Key Settings', null, 'license_manager_settings');

    add_settings_field('license_manager_api_key', 'API Key', 'lm_license_manager_api_key_callback', 'license_manager_settings', 'license_manager_section');
}
add_action('admin_init', 'lm_license_manager_register_settings');

function lm_license_manager_api_key_callback() {
    $api_key = get_option('license_manager_api_key');
    if (isset($_POST['regenerate_api_key'])) {
        $api_key = wp_generate_password(32, false);
        update_option('license_manager_api_key', $api_key);
    }
    echo '<input type="text" name="license_manager_api_key" value="' . esc_attr($api_key) . '" readonly />';
    echo '<p class="description">Use this key to access the license API on this site.</p>';
    echo '<form method="post"><button type="submit" name="regenerate_api_key" class="button">Regenerate API Key</button></form>';
}

// Register the REST API endpoint for checking license status
function lm_register_license_status_endpoint() {
    register_rest_route('license-manager/v1', '/check-license/', array(
        'methods' => 'GET',
        'callback' => 'lm_check_license_status',
        'permission_callback' => '__return_true', // We'll handle permission manually
    ));
}
add_action('rest_api_init', 'lm_register_license_status_endpoint');

// Endpoint callback function to check the license status
function lm_check_license_status(WP_REST_Request $request) {
    $provided_key = $request->get_param('api_key');
    $license_key = $request->get_param('license_key');
    $stored_api_key = get_option('license_manager_api_key');

    if (empty($provided_key) || $provided_key !== $stored_api_key) {
        return new WP_REST_Response(array('error' => 'Invalid API key'), 403);
    }

    $license_posts = get_posts(array(
        'post_type' => 'license',
        'meta_query' => array(
            array(
                'key' => '_license_key',
                'value' => $license_key,
                'compare' => '='
            )
        )
    ));

    if (empty($license_posts)) {
        return new WP_REST_Response(array('status' => 'not found'), 404);
    }

    $license_status = get_post_meta($license_posts[0]->ID, '_status', true);
    $expiration_date = get_post_meta($license_posts[0]->ID, '_expiration_date', true);

    if ($license_status === 'expired') {
        return new WP_REST_Response(array('status' => 'expired', 'expiration_date' => $expiration_date), 200);
    }

    return new WP_REST_Response(array(
        'status' => $license_status,
        'expiration_date' => $expiration_date
    ), 200);
}

// Add custom "Status" column to the License list table in the admin
function lm_add_license_status_column($columns) {
    $columns['license_status'] = 'Status';
    return $columns;
}
add_filter('manage_license_posts_columns', 'lm_add_license_status_column');

// Populate the "Status" column with color-coded status labels
function lm_fill_license_status_column($column, $post_id) {
    if ($column === 'license_status') {
        $status = get_post_meta($post_id, '_status', true);
        
        // Define color classes for each status
        $color = 'gray'; // Default color
        if ($status === 'active') {
            $color = 'green';
        } elseif ($status === 'inactive') {
            $color = 'darkgray';
        } elseif ($status === 'expired') {
            $color = 'red';
        }

        // Display the status with colored styling
        echo '<span style="color:' . $color . ';">' . ucfirst($status) . '</span>';
    }
}
add_action('manage_license_posts_custom_column', 'lm_fill_license_status_column', 10, 2);

// Make the new "Status" column sortable
function lm_license_sortable_columns($columns) {
    $columns['license_status'] = 'license_status';
    return $columns;
}
add_filter('manage_edit-license_sortable_columns', 'lm_license_sortable_columns');

// Handle sorting by status
function lm_license_orderby_status($query) {
    if (!is_admin()) {
        return;
    }

    $orderby = $query->get('orderby');
    if ('license_status' === $orderby) {
        $query->set('meta_key', '_status');
        $query->set('orderby', 'meta_value');
    }
}
add_action('pre_get_posts', 'lm_license_orderby_status');

