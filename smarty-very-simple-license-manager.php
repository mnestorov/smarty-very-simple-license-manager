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
function smarty_enqueue_admin_scripts($hook) {
    global $post_type;

    // Check if we're on the License Manager settings page
    if ($hook === 'settings_page_license_manager') {
        // Enqueue AJAX script for the settings page
        wp_enqueue_script('sm-license-manager-ajax', plugin_dir_url(__FILE__) . 'js/slm-ajax.js', array('jquery'), null, true);

        // Localize AJAX URL for the JavaScript
        wp_localize_script('sm-license-manager-ajax', 'smarty_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php')
        ));
    }

    // Check if we're on any license-related pages (edit, add, or post screen)
    if ($hook === 'edit.php' || $hook === 'post.php' || $hook === 'post-new.php') {
        if ($post_type === 'license' || get_post_type() === 'license') {
            // Enqueue CSS and JS files for license post type
            wp_enqueue_style('slm-admin-css', plugin_dir_url(__FILE__) . 'css/slm-admin.css');
            wp_enqueue_script('slm-admin-js', plugin_dir_url(__FILE__) . 'js/slm-admin.js', array(), null, true);
        }
    }
}
add_action('admin_enqueue_scripts', 'smarty_enqueue_admin_scripts');

/**
 * Register the custom post type for managing licenses.
 */
function smarty_register_license_post_type() {
    register_post_type('license', array(
        'labels' => array(
            'name'               => 'Licenses',
            'singular_name'      => 'License',
            'add_new'            => 'Add New License',
            'add_new_item'       => 'Add New License',
            'edit_item'          => 'Edit License',
            'new_item'           => 'New License',
            'view_item'          => 'View License',
            'search_items'       => 'Search Licenses',
            'not_found'          => 'No licenses found',
            'not_found_in_trash' => 'No licenses found in Trash',
        ),
        'public'        => true,
        'has_archive'   => true,
        'supports'      => array('title'), // Only 'title' support, no editor
        'menu_icon'     => 'dashicons-admin-network',
        'show_in_rest'  => true,
    ));
}
add_action('init', 'smarty_register_license_post_type');

/**
 * Customize the update messages for the License custom post type.
 *
 * @param array $messages Default update messages.
 * @return array Modified update messages.
 */
function smarty_license_post_updated_messages($messages) {
    global $post, $post_ID;

    $messages['license'] = array(
        0 => '', // Unused. Messages start from index 1.
        1 => 'License updated.', // Updated
        2 => 'Custom field updated.',
        3 => 'Custom field deleted.',
        4 => 'License updated.',
        5 => isset($_GET['revision']) ? sprintf('License restored to revision from %s', wp_post_revision_title((int) $_GET['revision'], false)) : false,
        6 => 'License published.',
        7 => 'License saved.',
        8 => 'License submitted.',
        9 => sprintf(
            'License scheduled for: <strong>%1$s</strong>.',
            date_i18n('M j, Y @ G:i', strtotime($post->post_date))
        ),
        10 => 'License draft updated.'
    );

    return $messages;
}
add_filter('post_updated_messages', 'smarty_license_post_updated_messages');

/**
 * Add custom meta boxes for license details in the license edit screen, with status dot after the title.
 */
function smarty_add_license_meta_boxes() {
    add_meta_box(
        'license_details',
        smarty_license_meta_box_title(), // Set title with dynamic status dot
        'smarty_license_details_callback',
        'license',
        'normal',
        'default'
    );
}
add_action('add_meta_boxes', 'smarty_add_license_meta_boxes');

/**
 * Generate the title for the license meta box with a colored status dot.
 *
 * @return string The meta box title with a status dot.
 */
function smarty_license_meta_box_title() {
    global $post;

    // Get the license status
    $status = get_post_meta($post->ID, '_status', true) ?: 'new';

    // Determine the color for the dot based on status
    $dot_color = $status === 'active' ? 'green' : ($status === 'inactive' ? 'red' : 'gray');

    // Return the title with a colored dot
    return 'License Details <span class="status-circle" style="background-color:' . $dot_color . ';"></span>';
}

/**
 * Callback function to render the license details meta box.
 *
 * @param WP_Post $post The current post object.
 */
function smarty_license_details_callback($post) {
    // Retrieve existing values from the post meta, if available
    $product_terms = get_the_terms($post->ID, 'product'); // Get assigned product terms
    $license_key = get_post_meta($post->ID, '_license_key', true);
    $client_name = get_post_meta($post->ID, '_client_name', true);
    $client_email = get_post_meta($post->ID, '_client_email', true);
    $purchase_date = get_post_meta($post->ID, '_purchase_date', true);
    $expiration_date = get_post_meta($post->ID, '_expiration_date', true);
    $status = get_post_meta($post->ID, '_status', true);
    $usage_url = get_post_meta($post->ID, '_usage_url', true); // Retrieve the usage URL
    $wp_version = get_post_meta($post->ID, '_wp_version', true); // Retrieve the WordPress version ?>

    <!-- Two-column layout styling -->
    <div style="display: flex; gap: 20px;">

        <!-- Left column with main fields -->
        <div style="flex: 1; padding: 10px;">
            <table class="license-table">
                <!-- License Key with Generate Button -->
                <tr>
                    <td><label><?php echo __('License Key', 'smarty-very-simple-license-manager'); ?></label></td>
                    <td>
                        <div class="field-wrapper">
                            <input type="text" name="license_key" id="license_key" value="<?php echo esc_attr($license_key); ?>" readonly />
                            <button type="button" class="button generate-key-button" onclick="generateLicenseKey()"><?php echo __('Generate Key', 'smarty-very-simple-license-manager'); ?></button>
                        </div>
                    </td>
                </tr>
                 <!-- Product -->
                <tr>
                    <td><label><?php echo __('Product', 'smarty-very-simple-license-manager'); ?></label></td>
                    <td>
                        <?php
                        wp_dropdown_categories(array(
                            'taxonomy' => 'product',
                            'name' => 'product',
                            'show_option_none' => 'Select a Product',
                            'selected' => $product_terms ? $product_terms[0]->term_id : '',
                            'required' => true,
                            'hide_empty' => false,
                        ));
                        ?>
                    </td>
                </tr>
                <!-- Client Name -->
                <tr>
                    <td><label><?php echo __('Client Name', 'smarty-very-simple-license-manager'); ?></label></td>
                    <td><input type="text" name="client_name" value="<?php echo esc_attr($client_name); ?>" required/></td>
                </tr>
                <!-- Client Email -->
                <tr>
                    <td><label><?php echo __('Client Email', 'smarty-very-simple-license-manager'); ?></label></td>
                    <td><input type="email" name="client_email" value="<?php echo esc_attr($client_email); ?>"/></td>
                </tr>
                <!-- Purchase Date -->
                <tr>
                    <td><label><?php echo __('Purchase Date', 'smarty-very-simple-license-manager'); ?></label></td>
                    <td><input type="date" name="purchase_date" value="<?php echo esc_attr($purchase_date); ?>"/></td>
                </tr>
                <!-- Expiration Date -->
                <tr>
                    <td><label><?php echo __('Expiration Date', 'smarty-very-simple-license-manager'); ?></label></td>
                    <td><input type="date" name="expiration_date" value="<?php echo esc_attr($expiration_date); ?>"/></td>
                </tr>
                <!-- Status -->
                <tr>
                    <td><label><?php echo __('Status', 'smarty-very-simple-license-manager'); ?></label></td>
                    <td>
                        <select name="status">
                            <?php foreach (array('active', 'inactive', 'expired') as $option) : ?>
                                <?php $selected = $status === $option ? 'selected' : ''; ?>
                                <option value="<?php echo $option; ?>" <?php echo $selected; ?>><?php echo ucfirst($option); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            </table>
        </div> <!-- End left column -->

        <!-- Right column with Usage URL -->
        <div style="flex: 1; padding: 10px; border-left: 1px solid #ddd;">
            <table class="license-table">
            <!-- Usage URL -->
            <tr>
                <td><label><?php echo __('Usage URL', 'smarty-very-simple-license-manager'); ?></label></td>
                <td><input type="text" name="usage_url" value="<?php echo ($usage_url ? esc_url($usage_url) : 'No usage URL recorded yet'); ?>" readonly /></td>
            </tr>
            <!-- WordPress Version -->
            <tr>
                <td><label><?php echo __('WP Version', 'smarty-very-simple-license-manager'); ?></label></td>
                <td><input type="text" name="usage_url" value="<?php echo ($wp_version ? esc_html($wp_version) : 'Not recorded yet'); ?>" readonly /></td>
            </tr>
            </table>
        </div> <!-- End right column -->

    </div> <!-- End two-column layout -->

    <?php
    // Add nonce field for security
    wp_nonce_field('smarty_save_license_meta', 'smarty_license_nonce');
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
        update_post_meta($post_id, '_client_name', sanitize_text_field($_POST['client_name']));
        update_post_meta($post_id, '_client_email', sanitize_email($_POST['client_email']));
        update_post_meta($post_id, '_purchase_date', sanitize_text_field($_POST['purchase_date']));
        update_post_meta($post_id, '_expiration_date', sanitize_text_field($_POST['expiration_date']));
        update_post_meta($post_id, '_status', sanitize_text_field($_POST['status']));

        if (isset($_POST['product'])) {
            wp_set_post_terms($post_id, array(intval($_POST['product'])), 'product');
        }
    }
}
add_action('save_post', 'smarty_save_license_meta', 10, 2);

/**
 * Set a numeric slug for licenses instead of a title-based slug.
 *
 * @param array $data The array of sanitized post data.
 * @param array $postarr The array of unsanitized post data.
 * @return array Modified post data with a numeric slug.
 */
function smarty_set_numeric_slug($data, $postarr) {
    if ($data['post_type'] === 'license' && $data['post_status'] === 'publish') {
        // Generate a unique numeric slug based on the post ID
        if (empty($postarr['ID'])) {
            // If it's a new post, use the current timestamp
            $numeric_slug = time();
        } else {
            // For existing posts, use the post ID
            $numeric_slug = $postarr['ID'];
        }
        $data['post_name'] = $numeric_slug; // Set the slug to be the numeric value
    }
    return $data;
}
add_filter('wp_insert_post_data', 'smarty_set_numeric_slug', 10, 2);

/**
 * Register the 'Products' taxonomy for licenses.
 */
function smarty_register_product_taxonomy() {
    register_taxonomy('product', 'license', array(
        'labels' => array(
            'name'          => 'Products',
            'singular_name' => 'Product',
            'search_items'  => 'Search Products',
            'all_items'     => 'All Products',
            'edit_item'     => 'Edit Product',
            'view_item'     => 'View Product',
            'add_new_item'  => 'Add New Product',
            'new_item_name' => 'New Product Name',
            'menu_name'     => 'Products',
        ),
        'hierarchical'  => true,
        'show_ui'       => true,
        'show_in_rest'  => true,
        'query_var'     => true,
        'rewrite'       => array('slug' => 'product'),
    ));
}
add_action('init', 'smarty_register_product_taxonomy');

/**
 * Remove the "View" link from the row actions for licenses in the admin list.
 *
 * @param array $actions The current row actions.
 * @param WP_Post $post The current post object.
 * @return array Modified row actions without the "View" link.
 */
function smarty_remove_view_link($actions, $post) {
    // Check if the current post type is "license"
    if ($post->post_type === 'license') {
        unset($actions['view']); // Remove the "View" action
    }
    return $actions;
}
add_filter('post_row_actions', 'smarty_remove_view_link', 10, 2);

/**
 * Remove "Quick Edit" from the row actions in the licenses list.
 *
 * @param array $actions The current row actions.
 * @param WP_Post $post The current post object.
 * @return array Modified row actions without the "Quick Edit" option.
 */
function smarty_remove_quick_edit($actions, $post) {
    if ($post->post_type === 'license') {
        unset($actions['inline hide-if-no-js']); // Remove the "Quick Edit" action
    }
    return $actions;
}
add_filter('post_row_actions', 'smarty_remove_quick_edit', 10, 2);

/**
 * Add custom columns for License Key, Status, Expiration Date, and User Email in the licenses list table.
 *
 * @param array $columns The current list of columns.
 * @return array Modified list of columns.
 */
function smarty_add_license_columns($columns) {
    unset($columns['title']);
    // Define the new columns order, placing "Product" first
    $new_columns = array(
        'product'         => 'Product',
        'license_key'     => 'License Key',
        'purchase_date'   => 'Purchase Date',
        'expiration_date' => 'Expiration Date',
        'client_name'     => 'Client Name',
        'client_email'    => 'Client Email',
        'license_status'  => 'Status',
    );

    return $new_columns;
}
add_filter('manage_license_posts_columns', 'smarty_add_license_columns');

/**
 * Populate the custom columns for License Key, Status, Expiration Date, and User Email.
 *
 * @param string $column The name of the column.
 * @param int $post_id The ID of the current post.
 */
function smarty_fill_license_columns($column, $post_id) {
    if ($column === 'license_key') {
        $license_key = get_post_meta($post_id, '_license_key', true);
        $masked_key = substr($license_key, 0, 4) . '-****-****-****';

        echo '<div class="license-key-wrapper">';
        
        // Masked key
        echo '<span class="masked-key" style="vertical-align: middle;">' . esc_html($masked_key) . '</span>';
        echo '<input type="hidden" class="full-key" value="' . esc_attr($license_key) . '" />';
    
        // Show/Hide and Copy links
        echo '<div class="key-toggle-links">';
        echo '<a href="#" class="row-actions show-key-link">Show</a>';
        echo '<a href="#" class="row-actions hide-key-link" style="display:none;">Hide</a>';
        echo '<span class="row-actions">|</span>';
        echo '<a href="#" class="row-actions copy-key-link" onclick="copyLicenseKey(this, \'' . esc_attr($license_key) . '\')">Copy</a>';
        echo '</div>';
        
        echo '</div>';
    } elseif ($column === 'license_status') {
        $status = get_post_meta($post_id, '_status', true);
        $status_text = ucfirst($status);
        
        if ($status === 'active') {
            echo '<span class="status-badge active">' . $status_text . '</span>';
        } elseif ($status === 'inactive') {
            echo '<span class="status-badge inactive">' . $status_text . '</span>';
        } else {
            echo '<span>' . $status_text . '</span>';
        }
    } elseif ($column === 'purchase_date') {
        $purchase_date = get_post_meta($post_id, '_purchase_date', true);
        $formatted_purchase_date = date('Y/m/d', strtotime($purchase_date)); // Format as YYYY/MM/DD
        echo esc_html($formatted_purchase_date);
    } elseif ($column === 'expiration_date') {
        $expiration_date = get_post_meta($post_id, '_expiration_date', true);
        $formatted_expiration_date = date('Y/m/d', strtotime($expiration_date)); // Format as YYYY/MM/DD
        echo esc_html($formatted_expiration_date);
    } elseif ($column === 'client_name') {
        echo esc_html(get_post_meta($post_id, '_client_name', true));
    } elseif ($column === 'client_email') {
        echo esc_html(get_post_meta($post_id, '_client_email', true));
    } elseif ($column === 'product') {
        // Display the product name(s)
        $product_terms = get_the_terms($post_id, 'product');
        if (!empty($product_terms) && !is_wp_error($product_terms)) {
            $product_names = wp_list_pluck($product_terms, 'name');
            echo esc_html(implode(', ', $product_names));
        } else {
            echo '—'; // Display a dash if no product is assigned
        }
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
    if (!wp_next_scheduled('smarty_license_check')) {
        wp_schedule_event(time(), 'daily', 'smarty_license_check');
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
add_action('smarty_license_check', 'smarty_check_expired_licenses');

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
        <h1><?php __('License Manager Settings', 'smarty-very-simple-license-manager'); ?></h1>
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
 * Register settings for CK_KEY and CS_KEY in License Manager.
 */
function smarty_license_manager_register_settings() {
    register_setting('license_manager_settings', 'license_manager_ck_key');
    register_setting('license_manager_settings', 'license_manager_cs_key');

    add_settings_section('license_manager_section', 'License Manager | Settings', null, 'license_manager_settings');

    add_settings_field('license_manager_ck_key', 'CK Key', 'smarty_license_manager_ck_key_callback', 'license_manager_settings', 'license_manager_section');
    add_settings_field('license_manager_cs_key', 'CS Key', 'smarty_license_manager_cs_key_callback', 'license_manager_settings', 'license_manager_section');
}
add_action('admin_init', 'smarty_license_manager_register_settings');

/**
 * Callback to display and regenerate the CK Key field.
 */
function smarty_license_manager_ck_key_callback() {
    $ck_key = get_option('license_manager_ck_key');
    echo '<input type="text" id="ck_key" name="license_manager_ck_key" value="' . esc_attr($ck_key) . '" readonly />';
    echo '<button type="button" id="generate_ck_key" class="button">Generate CK Key</button>';
    echo '<p class="description">This CK Key is used for API authentication. Click "Generate CK Key" to create a new one.</p>';
}

/**
 * Callback to display and regenerate the CS Key field.
 */
function smarty_license_manager_cs_key_callback() {
    $cs_key = get_option('license_manager_cs_key');
    echo '<input type="text" id="cs_key" name="license_manager_cs_key" value="' . esc_attr($cs_key) . '" readonly />';
    echo '<button type="button" id="generate_cs_key" class="button">Generate CS Key</button>';
    echo '<p class="description">This CS Key is used as a secret key for API requests. Click "Generate CS Key" to create a new one.</p>';
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
 * Callback for the REST API endpoint to check license status and log usage URL.
 *
 * @param WP_REST_Request $request The REST request object.
 * @return WP_REST_Response The REST response with license status.
 */
function smarty_check_license_status(WP_REST_Request $request) {
    $provided_key = $request->get_param('api_key');
    $license_key = $request->get_param('license_key');
    $site_url = $request->get_param('site_url'); // URL of the site using the license
    $wp_version = $request->get_param('wp_version'); // WordPress version from client site
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

    $license_id = $license_posts[0]->ID;

    // Save the site URL and WordPress version if provided and valid
    if (!empty($site_url) && filter_var($site_url, FILTER_VALIDATE_URL)) {
        update_post_meta($license_id, '_usage_url', esc_url_raw($site_url));
    }
    if (!empty($wp_version)) {
        update_post_meta($license_id, '_wp_version', sanitize_text_field($wp_version));
    }

    $license_status = get_post_meta($license_id, '_status', true);
    $expiration_date = get_post_meta($license_id, '_expiration_date', true);

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

/**
 * AJAX handler to generate a CK key.
 */
function smarty_generate_ck_key() {
    $ck_key = 'ck_' . bin2hex(random_bytes(20)); // Generate a CK key
    update_option('license_manager_ck_key', $ck_key);
    wp_send_json_success($ck_key);
}
add_action('wp_ajax_generate_ck_key', 'smarty_generate_ck_key');

/**
 * AJAX handler to generate a CS key.
 */
function smarty_generate_cs_key() {
    $cs_key = 'cs_' . bin2hex(random_bytes(20)); // Generate a CS key
    update_option('license_manager_cs_key', $cs_key);
    wp_send_json_success($cs_key);
}
add_action('wp_ajax_generate_cs_key', 'smarty_generate_cs_key');

