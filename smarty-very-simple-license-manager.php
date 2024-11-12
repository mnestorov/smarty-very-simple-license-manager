<?php
/**
 * Plugin Name:             SM - Very Simple License Manager
 * Plugin URI:              https://github.com/mnestorov/smarty-very-simple-license-manager
 * Description:             A plugin to manage licenses with custom post types, status management, and API keys.
 * Version:                 1.0.0
 * Author:                  Smarty Studio | Martin Nestorov
 * Author URI:              https://github.com/mnestorov
 * License:                 GPL-2.0+
 * License URI:             http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:             smarty-very-simple-license-manager
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
	die;
}

/**
 * Enqueue admin-specific assets (CSS and JavaScript) for the license post type edit screen.
 *
 * @param string $hook The current admin page.
 */
function smarty_enqueue_admin_assets($hook) {
    // Load scripts and styles only on the edit screen for the license post type
    global $post;
    if ($hook === 'post.php' || $hook === 'post-new.php') {
        if ($post && $post->post_type === 'license') {
            // Enqueue CSS file
            wp_enqueue_style('slm-admin-css', plugin_dir_url(__FILE__) . 'css/slm-admin.css');
            
            // Enqueue JavaScript file
            wp_enqueue_script('slm-admin-js', plugin_dir_url(__FILE__) . 'js/slm-admin.js', array(), null, true);
        }
    }
}
add_action('admin_enqueue_scripts', 'smarty_enqueue_admin_assets');

/**
 * Register the custom post type for managing licenses.
 */
function smarty_register_license_post_type() {
    register_post_type('license', array(
        'labels' => array(
            'name' => 'Licenses',
            'singular_name' => 'License',
            'add_new' => 'Add New License',
            'add_new_item' => 'Add New License',
            'edit_item' => 'Edit License',
            'new_item' => 'New License',
            'view_item' => 'View License',
            'search_items' => 'Search Licenses',
            'not_found' => 'No licenses found',
            'not_found_in_trash' => 'No licenses found in Trash',
        ),
        'public' => true,
        'has_archive' => true,
        'supports' => array('title'), // Only 'title' support, no editor
        'show_in_rest' => true,
    ));
}
add_action('init', 'smarty_register_license_post_type');

/**
 * Add custom meta boxes for license details in the license edit screen.
 */
function smarty_add_license_meta_boxes() {
    add_meta_box('license_details', 'License Details', 'smarty_license_details_callback', 'license', 'normal', 'default');
}
add_action('add_meta_boxes', 'smarty_add_license_meta_boxes');

/**
 * Callback function to render the license details meta box.
 *
 * @param WP_Post $post The current post object.
 */
function smarty_license_details_callback($post) {
    // Retrieve existing values from the post meta, if available
    $license_key = get_post_meta($post->ID, '_license_key', true);
    $user_email = get_post_meta($post->ID, '_user_email', true);
    $purchase_date = get_post_meta($post->ID, '_purchase_date', true);
    $expiration_date = get_post_meta($post->ID, '_expiration_date', true);
    $status = get_post_meta($post->ID, '_status', true);

    // Display the license details form as a table
    echo '<div style="padding: 10px;">';
    echo '<table class="license-table">';

    // License Key with Generate Button
    echo '<tr>';
    echo '<td><label>License Key</label></td>';
    echo '<td>
            <div class="field-wrapper">
                <input type="text" name="license_key" id="license_key" value="' . esc_attr($license_key) . '" readonly />
                <button type="button" class="button generate-key-button" onclick="generateLicenseKey()">Generate Key</button>
            </div>
          </td>';
    echo '</tr>';

    // User Email
    echo '<tr>';
    echo '<td><label>User Email</label></td>';
    echo '<td><input type="email" name="user_email" value="' . esc_attr($user_email) . '"/></td>';
    echo '</tr>';

    // Purchase Date
    echo '<tr>';
    echo '<td><label>Purchase Date</label></td>';
    echo '<td><input type="date" name="purchase_date" value="' . esc_attr($purchase_date) . '"/></td>';
    echo '</tr>';

    // Expiration Date
    echo '<tr>';
    echo '<td><label>Expiration Date</label></td>';
    echo '<td><input type="date" name="expiration_date" value="' . esc_attr($expiration_date) . '"/></td>';
    echo '</tr>';

    // Status
    echo '<tr>';
    echo '<td><label>Status</label></td>';
    echo '<td><select name="status">';
    foreach (array('active', 'inactive', 'expired') as $option) {
        $selected = $status === $option ? 'selected' : '';
        echo '<option value="' . $option . '" ' . $selected . '>' . ucfirst($option) . '</option>';
    }
    echo '</select></td>';
    echo '</tr>';

    echo '</table>';
    
    wp_nonce_field('smarty_save_license_meta', 'smarty_license_nonce'); // Add nonce field for security
    echo '</div>';
}

/**
 * Save the license meta data with nonce verification.
 *
 * @param int $post_id The ID of the current post being saved.
 * @param WP_Post $post The current post object.
 */
function smarty_save_license_meta($post_id, $post) {
    if ($post->post_type === 'license') {
        // Verify nonce for security
        if (!isset($_POST['smarty_license_nonce']) || !wp_verify_nonce($_POST['smarty_license_nonce'], 'smarty_save_license_meta')) {
            return $post_id;
        }

        // Auto-generate license key if none exists
        $license_key = sanitize_text_field($_POST['license_key']);
        if (empty($license_key)) {
            $license_key = strtoupper(wp_generate_password(16, false, false));
        }
        update_post_meta($post_id, '_license_key', $license_key);

        // Update other fields
        update_post_meta($post_id, '_user_email', sanitize_email($_POST['user_email']));
        update_post_meta($post_id, '_purchase_date', sanitize_text_field($_POST['purchase_date']));
        update_post_meta($post_id, '_expiration_date', sanitize_text_field($_POST['expiration_date']));
        update_post_meta($post_id, '_status', sanitize_text_field($_POST['status']));
    }
}
add_action('save_post', 'smarty_save_license_meta', 10, 2);

/**
 * Add custom columns for License Key and Status in the licenses list table.
 *
 * @param array $columns The current list of columns.
 * @return array Modified list of columns.
 */
function smarty_add_license_columns($columns) {
    $columns['license_key'] = 'License Key';
    $columns['license_status'] = 'Status';
    return $columns;
}
add_filter('manage_license_posts_columns', 'smarty_add_license_columns');

/**
 * Populate the custom columns for License Key and Status.
 *
 * @param string $column The name of the column.
 * @param int $post_id The ID of the current post.
 */
function smarty_fill_license_columns($column, $post_id) {
    if ($column === 'license_key') {
        // Display License Key
        echo esc_html(get_post_meta($post_id, '_license_key', true));
    } elseif ($column === 'license_status') {
        // Display Status with color styling
        $status = get_post_meta($post_id, '_status', true);
        $color = 'gray';
        
        if ($status === 'active') $color = 'green';
        elseif ($status === 'inactive') $color = 'darkgray';
        elseif ($status === 'expired') $color = 'red';

        echo '<span style="color:' . $color . ';">' . ucfirst($status) . '</span>';
    }
}
add_action('manage_license_posts_custom_column', 'smarty_fill_license_columns', 10, 2);

/**
 * Define sortable columns for License Key and Status.
 *
 * @param array $columns The current list of sortable columns.
 * @return array Modified list of sortable columns.
 */
function smarty_sortable_license_columns($columns) {
    $columns['license_key'] = 'license_key';
    $columns['license_status'] = 'license_status';
    return $columns;
}
add_filter('manage_edit-license_sortable_columns', 'smarty_sortable_license_columns');

/**
 * Modify the query to handle sorting by license key and status.
 *
 * @param WP_Query $query The current WP_Query instance.
 */
function smarty_orderby_license_columns($query) {
    if (!is_admin()) return;
    if ($query->get('orderby') === 'license_key') {
        $query->set('meta_key', '_license_key');
        $query->set('orderby', 'meta_value');
    }
    if ($query->get('orderby') === 'license_status') {
        $query->set('meta_key', '_status');
        $query->set('orderby', 'meta_value');
    }
}
add_action('pre_get_posts', 'smarty_orderby_license_columns');

/**
 * Schedule a daily cron job to check for expired licenses.
 */
function smarty_schedule_cron_job() {
    if (!wp_next_scheduled('smarty_license_cron_job')) {
        wp_schedule_event(time(), 'daily', 'smarty_license_cron_job');
    }
}
add_action('wp', 'smarty_schedule_cron_job');

/**
 * Cron job function to mark expired licenses as expired.
 */
function smarty_check_expired_licenses() {
    $licenses = get_posts(array('post_type' => 'license', 'posts_per_page' => -1));
    foreach ($licenses as $license) {
        $expiration_date = get_post_meta($license->ID, '_expiration_date', true);
        if (strtotime($expiration_date) < time()) {
            update_post_meta($license->ID, '_status', 'expired');
        }
    }
}
add_action('smarty_license_cron_job', 'smarty_check_expired_licenses');

/**
 * Create settings page for API key management.
 */
function smarty_license_manager_settings_page() {
    add_options_page('License Manager Settings', 'License Manager', 'manage_options', 'license_manager', 'smarty_license_manager_settings_page_callback');
}
add_action('admin_menu', 'smarty_license_manager_settings_page');

/**
 * Callback to render the License Manager Settings page.
 */
function smarty_license_manager_settings_page_callback() {
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

/**
 * Register settings for API key management in License Manager.
 */
function smarty_license_manager_register_settings() {
    register_setting('license_manager_settings', 'license_manager_api_key');
    add_settings_section('license_manager_section', 'API Key Settings', null, 'license_manager_settings');

    add_settings_field('license_manager_api_key', 'API Key', 'smarty_license_manager_api_key_callback', 'license_manager_settings', 'license_manager_section');
}
add_action('admin_init', 'smarty_license_manager_register_settings');

/**
 * Callback function to display and regenerate the API key field.
 */
function smarty_license_manager_api_key_callback() {
    $api_key = get_option('license_manager_api_key');
    if (isset($_POST['regenerate_api_key'])) {
        $api_key = wp_generate_password(32, false);
        update_option('license_manager_api_key', $api_key);
    }
    echo '<input type="text" name="license_manager_api_key" value="' . esc_attr($api_key) . '" readonly />';
    echo '<p class="description">Use this key to access the license API on this site.</p>';
    echo '<form method="post"><button type="submit" name="regenerate_api_key" class="button">Regenerate API Key</button></form>';
}

/**
 * Register REST API endpoint for license status check.
 */
function smarty_register_license_status_endpoint() {
    register_rest_route('license-manager/v1', '/check-license/', array(
        'methods' => 'GET',
        'callback' => 'smarty_check_license_status',
        'permission_callback' => '__return_true', // We'll handle permission manually
    ));
}
add_action('rest_api_init', 'smarty_register_license_status_endpoint');

/**
 * Callback for the REST API endpoint to check license status.
 *
 * @param WP_REST_Request $request The REST request object.
 * @return WP_REST_Response The REST response with license status.
 */
function smarty_check_license_status(WP_REST_Request $request) {
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

/**
 * Make the Status column sortable in the admin.
 *
 * @param array $columns List of sortable columns.
 * @return array Updated sortable columns.
 */
function smarty_license_sortable_columns($columns) {
    $columns['license_status'] = 'license_status';
    return $columns;
}
add_filter('manage_edit-license_sortable_columns', 'smarty_license_sortable_columns');

/**
 * Sort the licenses by status in the admin.
 *
 * @param WP_Query $query The current WP_Query object.
 */
function smarty_license_orderby_status($query) {
    if (!is_admin()) {
        return;
    }

    $orderby = $query->get('orderby');
    if ('license_status' === $orderby) {
        $query->set('meta_key', '_status');
        $query->set('orderby', 'meta_value');
    }
}
add_action('pre_get_posts', 'smarty_license_orderby_status');

