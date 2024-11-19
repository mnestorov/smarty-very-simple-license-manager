<?php
/**
 * Plugin Name:     SM - Very Simple License Manager
 * Plugin URI:      https://github.com/mnestorov/smarty-very-simple-license-manager
 * Description:     A plugin to manage licenses with custom post types, status management, and API keys.
 * Version:         1.0.0
 * Author:          Smarty Studio | Martin Nestorov
 * Author URI:      https://github.com/mnestorov
 * License:         GPL-2.0+
 * License URI:     http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:     smarty-very-simple-license-manager
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
	die;
}

/////////////////////////////////////////////////////////
// Enqueue Scripts and Styles for Admin
/////////////////////////////////////////////////////////

if (!function_exists('smarty_vslm_enqueue_admin_scripts')) {
    /**
     * Enqueue admin-specific assets (CSS and JavaScript) for the license post type edit screen.
     *
     * @param string $hook The current admin page.
     */
    function smarty_vslm_enqueue_admin_scripts($hook) {
        global $post_type;

        // Check if we're on the License Manager settings page
        if ($hook === 'settings_page_smarty-vslm-settings') {
            // Enqueue AJAX script for the settings page
            wp_enqueue_script('vslm-ajax', plugin_dir_url(__FILE__) . 'js/smarty-vslm-ajax.js', array('jquery'), null, true);

            // Localize AJAX URL for the JavaScript
            wp_localize_script('vslm-ajax', 'smarty_vslm_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php')
            ));
        }

        // Check if we're on any license-related pages (edit, add, or post screen)
        if ($hook === 'edit.php' || $hook === 'post.php' || $hook === 'post-new.php') {
            if ($post_type === 'vslm-licenses' || get_post_type() === 'vslm-licenses') {
                // Enqueue CSS and JS files for license post type
                wp_enqueue_style('vslm-admin-css', plugin_dir_url(__FILE__) . 'css/smarty-vslm-admin.css');
                wp_enqueue_script('vslm-admin-js', plugin_dir_url(__FILE__) . 'js/smarty-vslm-admin.js', array(), null, true);
                wp_enqueue_script('vslm-json-js', plugin_dir_url(__FILE__) . 'js/smarty-vslm-json.js', array(), null, true);
            }
        }
    }
    add_action('admin_enqueue_scripts', 'smarty_vslm_enqueue_admin_scripts');
}

/////////////////////////////////////////////////////////
// Dashboard Widget
/////////////////////////////////////////////////////////

if (!function_exists('smarty_vslm_add_dashboard_widget')) {
    /**
     * Register the custom dashboard widget.
     */
    function smarty_vslm_add_dashboard_widget() {
        wp_add_dashboard_widget(
            'smarty_vslm_dashboard_widget',         // Widget ID
            'License Manager Overview',             // Widget Title
            'smarty_vslm_dashboard_widget_render'   // Callback function to display content
        );
    }
    add_action('wp_dashboard_setup', 'smarty_vslm_add_dashboard_widget');
}

if (!function_exists('smarty_vslm_dashboard_widget_render')) {
    /**
     * Render the content of the custom dashboard widget with a centered icon and link.
     */
    function smarty_vslm_dashboard_widget_render() {
        // Query for licenses and count statuses
        $total_count = wp_count_posts('vslm-licenses')->publish; // Get total published licenses
        $active_count = smarty_vslm_get_license_count_by_status('active');
        $inactive_count = smarty_vslm_get_license_count_by_status('inactive');
        $expired_count = smarty_vslm_get_license_count_by_status('expired');

        // Begin widget content
        echo '<div style="padding: 5px;">';

        // Centered icon and link for total licenses
        echo '<h3 style="margin: 0; display: flex; align-items: center; gap: 5px;">';
        echo '<a href="edit.php?post_type=vslm-licenses" style="font-size: 1.2em; color: #135e96; text-decoration: none;">' . $total_count . ' Licenses</a>';
        echo '</h3>';

        // Table with detailed license counts
        echo '<table style="width: 100%; border-collapse: collapse; margin-top: 10px;">';
        echo '<thead>';
        echo '<tr style="background-color: #f5f5f5; border-bottom: 1px solid #ddd;">';
        echo '<th style="border-top: 1px solid #ddd; padding: 8px; text-align: left;">Status</th>';
        echo '<th style="border-top: 1px solid #ddd; padding: 8px; text-align: center;">Count</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        echo '<tr>';
        echo '<td style="border-top: 1px solid #ddd; padding: 8px; color: #28a745; font-weight: bold;">Active Licenses</td>';
        echo '<td style="border-top: 1px solid #ddd; padding: 8px; text-align: center; font-weight: bold;">' . $active_count . '</td>';
        echo '</tr>';
        echo '<tr>';
        echo '<td style="border-top: 1px solid #ddd; padding: 8px; color: #dc3545; font-weight: bold;">Inactive Licenses</td>';
        echo '<td style="border-top: 1px solid #ddd; padding: 8px; text-align: center; font-weight: bold;">' . $inactive_count . '</td>';
        echo '</tr>';
        echo '<tr>';
        echo '<td style="border-top: 1px solid #ddd; padding: 8px; color: #427eab; font-weight: bold;">Expired Licenses</td>';
        echo '<td style="border-top: 1px solid #ddd; padding: 8px; text-align: center; font-weight: bold;">' . $expired_count . '</td>';
        echo '</tr>';
        echo '</tbody>';
        echo '</table>';

        echo '</div>';
    }
}

if (!function_exists('smarty_vslm_get_license_count_by_status')) {
    /**
     * Helper function to get the count of licenses by status.
     *
     * @param string $status The license status to count.
     * @return int The count of licenses with the given status.
     */
    function smarty_vslm_get_license_count_by_status($status) {
        $query = new WP_Query(array(
            'post_type'      => 'vslm-licenses',
            'meta_key'       => '_status',
            'meta_value'     => $status,
            'posts_per_page' => -1,
            'fields'         => 'ids', // Only retrieve IDs for efficiency
        ));
        return $query->found_posts;
    }
}

/////////////////////////////////////////////////////////
// License Custom Post Type and Taxonomy
/////////////////////////////////////////////////////////

if (!function_exists('smarty_vslm_register_license_post_type')) {
    /**
     * Register the custom post type for managing licenses.
     */
    function smarty_vslm_register_license_post_type() {
        register_post_type('vslm-licenses', array(
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
    add_action('init', 'smarty_vslm_register_license_post_type');
}

if (!function_exists('smarty_vslm_license_post_updated_messages')) {
    /**
     * Customize the update messages for the License custom post type.
     *
     * @param array $messages Default update messages.
     * @return array Modified update messages.
     */
    function smarty_vslm_license_post_updated_messages($messages) {
        global $post, $post_ID;

        $messages['vslm-licenses'] = array(
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
    add_filter('post_updated_messages', 'smarty_vslm_license_post_updated_messages');
}

/////////////////////////////////////////////////////////
// Custom Meta Boxes on Add/Edit Page
/////////////////////////////////////////////////////////

if (!function_exists('smarty_vslm_add_license_meta_boxes')) {
    /**
     * Add custom meta boxes for license details in the license edit screen, with status dot after the title.
     */
    function smarty_vslm_add_license_meta_boxes() {
        add_meta_box(
            'license_details',
            smarty_vslm_license_meta_box_title(), // Set title with dynamic status dot
            'smarty_vslm_license_details_callback',
            'vslm-licenses',
            'normal',
            'default'
        );
    }
    add_action('add_meta_boxes', 'smarty_vslm_add_license_meta_boxes');
}

if (!function_exists('smarty_vslm_license_meta_box_title')) {
    /**
     * Generate the title for the license meta box with a colored status dot.
     *
     * @return string The meta box title with a status dot.
     */
    function smarty_vslm_license_meta_box_title() {
        global $post;

        // Get the license status
        $status = get_post_meta($post->ID, '_status', true) ?: 'new';

        // Determine the color for the dot based on status
		$dot_color = $status === 'active' ? '#28a745' : '#dc3545'; // Green if active, red if inactive

        // Set class based on status to handle the pulse effect only for 'active'
        $status_class = 'smarty-vslm-status-circle-container--' . $status;
		
        // Return the title with a container for the pulsing effect
        return  'LICENSE ' . '#' . $post->ID . '<span class="smarty-vslm-status-circle-container ' . esc_attr($status_class) . '"><span class="smarty-vslm-status-circle" style="background-color:' . esc_attr($dot_color) . ';"></span></span>';
    }
}

if (!function_exists('smarty_vslm_add_json_response_meta_box')) {
    /**
     * Add a meta box for displaying the JSON response from the plugin status endpoint.
     */
    function smarty_vslm_add_json_response_meta_box() {
        add_meta_box(
            'smarty_vslm_json_response',
            __('JSON Response', 'smarty-very-simple-license-manager'),
            'smarty_vslm_json_response_meta_box_callback',
            'vslm-licenses',
            'normal',
            'default'
        );
    }
    add_action('add_meta_boxes', 'smarty_vslm_add_json_response_meta_box');
}

if (!function_exists('smarty_vslm_json_response_meta_box_callback')) {
    /**
     * Callback for the Plugin Status JSON Response meta box.
     *
     * @param WP_Post $post The current post object.
     */
    function smarty_vslm_json_response_meta_box_callback($post) {
        // Retrieve meta data
        $multi_domain = get_post_meta($post->ID, '_multi_domain', true);
        $usage_url = get_post_meta($post->ID, '_usage_url', true);
        $usage_urls = get_post_meta($post->ID, '_usage_urls', true) ?: [];
        $plugin_name = get_post_meta($post->ID, '_plugin_name', true);
		$json_response_info = __('This is the plugin status response from client site.', 'smarty-very-simple-license-manager');

        // Validate plugin name
        if (empty($plugin_name)) {
            echo '<p class="error">' . __('Plugin Name is missing.', 'smarty-very-simple-license-manager') . '</p>';
            return;
        }

        // Handle single-domain usage
        if ($multi_domain !== '1') {
            if (empty($usage_url)) {
                echo '<p class="error">' . __('Usage URL is missing for single-domain usage.', 'smarty-very-simple-license-manager') . '</p>';
                return;
            }

            $endpoint = trailingslashit(esc_url($usage_url)) . 'wp-json/' . sanitize_title($plugin_name) . '/v1/plugin-status';
            ?>

			<p><?php echo $json_response_info; ?></p>
            <p>
                <strong><?php echo __('Client URL:', 'smarty-very-simple-license-manager'); ?></strong>
                <a href="<?php echo esc_url($endpoint); ?>" target="_blank"><?php echo esc_url($endpoint); ?></a>
            </p>
            <div class="smarty-json-response smarty-json-container" data-json-endpoint="<?php echo esc_url($endpoint); ?>">
                <p class="success"><?php echo __('Loading JSON response...', 'smarty-very-simple-license-manager'); ?></p>
            </div>
            <?php
        } else {
            // Handle multi-domain usage
            if (empty($usage_urls)) {
                echo '<p class="error">' . __('No usage URLs available for multi-domain usage.', 'smarty-very-simple-license-manager') . '</p>';
                return;
            } ?>
			
			<p><?php echo $json_response_info; ?></p><?php

            foreach ($usage_urls as $url_data) {
                if (isset($url_data['site_url']) && !empty($url_data['site_url'])) {
                    $endpoint = trailingslashit(esc_url($url_data['site_url'])) . 'wp-json/' . sanitize_title($plugin_name) . '/v1/plugin-status';
                    ?>
                    <p>
                        <strong><?php echo __('Client URL:', 'smarty-very-simple-license-manager'); ?></strong>
                        <a href="<?php echo esc_url($endpoint); ?>" target="_blank"><?php echo esc_url($endpoint); ?></a>
                    </p>
                    <div class="smarty-json-response smarty-json-container" data-json-endpoint="<?php echo esc_url($endpoint); ?>">
                        <p class="success"><?php echo __('Loading JSON response...', 'smarty-very-simple-license-manager'); ?></p>
                    </div>
                    <?php
                } else {
                    echo '<p class="error">' . __('Invalid or missing URL in multi-domain configuration.', 'smarty-very-simple-license-manager') . '</p>';
                }
            }
        }
    }
}

if (!function_exists('smarty_vslm_license_details_callback')) {
    /**
     * Callback function to render the license details meta box.
     *
     * @param WP_Post $post The current post object.
     */
    function smarty_vslm_license_details_callback($post) {
        // Retrieve existing values from the post meta, if available
        $product_terms = get_the_terms($post->ID, 'product'); // Get assigned product terms
        $license_key = get_post_meta($post->ID, '_license_key', true);
        $client_name = get_post_meta($post->ID, '_client_name', true);
        $client_email = get_post_meta($post->ID, '_client_email', true);
        $purchase_date = get_post_meta($post->ID, '_purchase_date', true);
        $expiration_date = get_post_meta($post->ID, '_expiration_date', true);
        $status = get_post_meta($post->ID, '_status', true);
        $usage_url = get_post_meta($post->ID, '_usage_url', true); // Retrieve the usage URL
        $usage_urls = get_post_meta($post->ID, '_usage_urls', true) ?: array();
        $multi_domain = get_post_meta($post->ID, '_multi_domain', true);
        $wp_version = get_post_meta($post->ID, '_wp_version', true); // Retrieve the WordPress version
		
        // Retrieve plugin information
        $plugin_name = get_post_meta($post->ID, '_plugin_name', true) ?: esc_html(__('Not recorded yet', 'smarty-very-simple-license-manager'));
        $plugin_version = get_post_meta($post->ID, '_plugin_version', true) ?: esc_html(__('Not recorded yet', 'smarty-very-simple-license-manager'));

        // Retrieve additional server information
        $web_server = get_post_meta($post->ID, '_web_server', true) ?: esc_html(__('Not recorded yet', 'smarty-very-simple-license-manager'));
        $server_ip = get_post_meta($post->ID, '_server_ip', true) ?: esc_html(__('Not recorded yet', 'smarty-very-simple-license-manager'));
        $php_version = get_post_meta($post->ID, '_php_version', true) ?: esc_html(__('Not recorded yet', 'smarty-very-simple-license-manager'));

        // Retrieve additional user info
        $user_ip = get_post_meta($post->ID, '_user_ip', true) ?: esc_html(__('Not recorded yet', 'smarty-very-simple-license-manager'));
        $browser = get_post_meta($post->ID, '_browser', true) ?: esc_html(__('Not recorded yet', 'smarty-very-simple-license-manager'));
        $device_type = get_post_meta($post->ID, '_device_type', true) ?: esc_html(__('Not recorded yet', 'smarty-very-simple-license-manager'));
        $os = get_post_meta($post->ID, '_os', true) ?: esc_html(__('Not recorded yet', 'smarty-very-simple-license-manager')); ?>

        <!-- Two-column layout styling -->
        <div class="smarty-vslm-two-col">
            <!-- Left Column -->
            <div class="smarty-vslm-left-col">
                <table class="smarty-vslm-license-table">
					<thead>
                    	<tr>
                        	<th colspan="2"><?php esc_html_e('License & Client Details', 'smarty-very-simple-license-manager'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
						<tr>
							<td>
								<table class="smarty-vslm-nested-table">
									<tr>
										<td><label><?php esc_html(_e('License Key', 'smarty-very-simple-license-manager')); ?></label></td>
										<td>
											<div class="smarty-vslm-field-wrapper">
												<input type="text" name="license_key" id="smarty_vslm_license_key" value="<?php echo esc_attr($license_key); ?>" readonly />
												<button type="button" class="button smarty-vslm-generate-key-button" onclick="generateLicenseKey()"><?php esc_html(_e('Generate Key', 'smarty-very-simple-license-manager')); ?></button>
											</div>
										</td>
									</tr>
									<tr>
										<td><label><?php esc_html(_e('Status', 'smarty-very-simple-license-manager')); ?></label></td>
										<td>
											<select name="status">
												<?php foreach (array('active', 'inactive', 'expired') as $option) : ?>
													<?php $selected = $status === $option ? 'selected' : ''; ?>
													<option value="<?php echo $option; ?>" <?php echo $selected; ?>><?php echo ucfirst($option); ?></option>
												<?php endforeach; ?>
											</select>
										</td>
									</tr>
									<tr>
										<td><label><?php esc_html(_e('Allow Multi-Domain Usage', 'smarty-very-simple-license-manager')); ?></label></td>
										<td>
											<label class="smarty-vslm-checkbox">
												<input type="checkbox" name="multi_domain" value="1" <?php checked($multi_domain, '1'); ?> />
												<span class="checkmark"></span>
											</label>
										</td>
									</tr>
									<!-- Product -->
									<tr>
										<td><label><?php esc_html(_e('Product', 'smarty-very-simple-license-manager')); ?></label></td>
										<td>
											<?php
											wp_dropdown_categories(array(
												'taxonomy'          => 'product',
												'name'              => 'product',
												'show_option_none'  => esc_html(__('-- Select a Product --', 'smarty-very-simple-license-manager')),
												'selected'          => $product_terms ? $product_terms[0]->term_id : '',
												'required'          => true,
												'hide_empty'        => false,
											));
											?>
										</td>
									</tr>
									<tr>
										<td><label><?php esc_html(_e('Client Name', 'smarty-very-simple-license-manager')); ?></label></td>
										<td><input type="text" name="client_name" value="<?php echo esc_attr($client_name); ?>" required/></td>
									</tr>
									<tr>
										<td><label><?php esc_html(_e('Client Email', 'smarty-very-simple-license-manager')); ?></label></td>
										<td><input type="email" name="client_email" value="<?php echo esc_attr($client_email); ?>"/></td>
									</tr>
									<tr>
										<td><label><?php esc_html(_e('Purchase Date', 'smarty-very-simple-license-manager')); ?></label></td>
										<td><input type="date" name="purchase_date" value="<?php echo esc_attr($purchase_date); ?>"/></td>
									</tr>
									<tr>
										<td><label><?php esc_html(_e('Expiration Date', 'smarty-very-simple-license-manager')); ?></label></td>
										<td><input type="date" name="expiration_date" value="<?php echo esc_attr($expiration_date); ?>"/></td>
									</tr>
								</table>
							</td>
						</tr>
					</tbody>
                </table>
            </div> <!-- End left column -->
			
            <!-- Right Column -->
            <div class="smarty-vslm-right-col">
                <?php if (!$multi_domain): ?>
                    <table class="smarty-vslm-license-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('URL Usage & Details', 'smarty-very-simple-license-manager'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
							<tr>
                                <td>
                                    <table class="smarty-vslm-nested-table">
										<thead>
											<tr>
												<th colspan="2" style="text-align: center;"><?php echo esc_html($usage_url ?? __('N/A', 'smarty-very-simple-license-manager')); ?></th>
											</tr>
										</thead>
                                        <tbody>
                                            <tr>
                                                <td><label><?php esc_html(_e('Plugin Name', 'smarty-very-simple-license-manager')); ?></label></td>
                                                <td><input type="text" name="plugin_name" value="<?php echo esc_html($plugin_name ?? __('N/A', 'smarty-very-simple-license-manager')); ?>" readonly /></td>
                                            </tr>
                                            <tr>
                                                <td><label><?php esc_html_e('Plugin Version', 'smarty-very-simple-license-manager'); ?></label></td>
                                                <td><input type="text" name="plugin_version" value="<?php echo esc_html($plugin_version ?? __('N/A', 'smarty-very-simple-license-manager')); ?>" readonly /></td>
                                            </tr>
                                            <tr>
                                                <td><label><?php esc_html_e('WP Version', 'smarty-very-simple-license-manager'); ?></label></td>
                                                <td><input type="text" name="wp_version" value="<?php echo esc_html($wp_version ?? __('N/A', 'smarty-very-simple-license-manager')); ?>" readonly /></td>
                                            </tr>
                                            <tr>
                                                <td><label><?php esc_html_e('Web Server', 'smarty-very-simple-license-manager'); ?></label></td>
                                                <td><input type="text" name="web_server" value="<?php echo esc_html($web_server ?? __('N/A', 'smarty-very-simple-license-manager')); ?>" readonly /></td>
                                            </tr>
                                            <tr>
                                                <td><label><?php esc_html_e('Server IP', 'smarty-very-simple-license-manager'); ?></label></td>
                                                <td><input type="text" name="server_ip" value="<?php echo esc_html($server_ip ?? __('N/A', 'smarty-very-simple-license-manager')); ?>" readonly /></td>
                                            </tr>
                                            <tr>
                                                <td><label><?php esc_html_e('PHP Version', 'smarty-very-simple-license-manager'); ?></label></td>
                                                <td><input type="text" name="php_version" value="<?php echo esc_html($php_version ?? __('N/A', 'smarty-very-simple-license-manager')); ?>" readonly /></td>
                                            </tr>
                                            <tr>
                                                <td><label><?php esc_html_e('User IP', 'smarty-very-simple-license-manager'); ?></label></td>
                                                <td><input type="text" name="user_ip" value="<?php echo esc_html($user_ip ?? __('N/A', 'smarty-very-simple-license-manager')); ?>" readonly /></td>
                                            </tr>
                                            <tr>
                                                <td><label><?php esc_html_e('Browser', 'smarty-very-simple-license-manager'); ?></label></td>
                                                <td><input type="text" name="browser" value="<?php echo esc_html($browser ?? __('N/A', 'smarty-very-simple-license-manager')); ?>" readonly /></td>
                                            </tr>
                                            <tr>
                                                <td><label><?php esc_html_e('Device Type', 'smarty-very-simple-license-manager'); ?></label></td>
                                                <td><input type="text" name="device_type" value="<?php echo esc_html($device_type ?? __('N/A', 'smarty-very-simple-license-manager')); ?>" readonly /></td>
                                            </tr>
                                            <tr>
                                                <td><label><?php esc_html_e('Operating System', 'smarty-very-simple-license-manager'); ?></label></td>
                                                <td><input type="text" name="os" value="<?php echo esc_html($os ?? __('N/A', 'smarty-very-simple-license-manager')); ?>" readonly /></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                <?php else: ?>
                    <?php if (!empty($usage_urls) && is_array($usage_urls)): ?>
						<table class="smarty-vslm-license-table">
							<thead>
								<tr>
									<th><?php esc_html_e('URL(s) Usage & Details', 'smarty-very-simple-license-manager'); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($usage_urls as $url_data): ?>
									<?php if (is_array($url_data) && isset($url_data['site_url'])): ?>
										<tr>
											<td>
												<table class="smarty-vslm-nested-table">
													<thead>
														<tr>
															<th colspan="2" style="text-align: center;"><?php echo esc_html($url_data['site_url'] ?? __('N/A', 'smarty-very-simple-license-manager')); ?></th>
														</tr>
													</thead>
                                                    <tbody>
														<tr>
															<td><label><?php esc_html(_e('Plugin Name', 'smarty-very-simple-license-manager')); ?></label></td>
															<td><input type="text" name="plugin_name" value="<?php echo esc_html($plugin_name); ?>" readonly /></td>
														</tr>
                                                        <tr>
                                                            <td><label><?php esc_html_e('Plugin Version:', 'smarty-very-simple-license-manager'); ?></label></td>
                                                            <td><input type="text" name="plugin_version" value="<?php echo esc_html($url_data['plugin_version'] ?? __('N/A', 'smarty-very-simple-license-manager')); ?>" readonly /></td>
                                                        </tr>
                                                        <tr>
                                                            <td><label><?php esc_html_e('WP Version:', 'smarty-very-simple-license-manager'); ?></label></td>
                                                            <td><input type="text" name="wp_version" value="<?php echo esc_html($url_data['wp_version'] ?? __('N/A', 'smarty-very-simple-license-manager')); ?>" readonly /></td>
                                                        </tr>
                                                        <tr>
                                                            <td><label><?php esc_html_e('Web Server:', 'smarty-very-simple-license-manager'); ?></label></td>
                                                            <td><input type="text" name="web_server" value="<?php echo esc_html($url_data['web_server'] ?? __('N/A', 'smarty-very-simple-license-manager')); ?>" readonly /></td>
                                                        </tr>
                                                        <tr>
                                                            <td><label><?php esc_html_e('Server IP:', 'smarty-very-simple-license-manager'); ?></label></td>
                                                            <td><input type="text" name="server_ip" value="<?php echo esc_html($url_data['server_ip'] ?? __('N/A', 'smarty-very-simple-license-manager')); ?>" readonly /></td>
                                                        </tr>
                                                        <tr>
                                                            <td><label><?php esc_html_e('PHP Version:', 'smarty-very-simple-license-manager'); ?></label></td>
                                                            <td><input type="text" name="php_version" value="<?php echo esc_html($url_data['php_version'] ?? __('N/A', 'smarty-very-simple-license-manager')); ?>" readonly /></td>
                                                        </tr>
                                                        <tr>
                                                            <td><label><?php esc_html_e('User IP:', 'smarty-very-simple-license-manager'); ?></label></td>
                                                            <td><input type="text" name="user_ip" value="<?php echo esc_html($url_data['user_ip'] ?? __('N/A', 'smarty-very-simple-license-manager')); ?>" readonly /></td>
                                                        </tr>
                                                        <tr>
                                                            <td><label><?php esc_html_e('Browser:', 'smarty-very-simple-license-manager'); ?></label></td>
                                                            <td><input type="text" name="browser" value="<?php echo esc_html($url_data['browser'] ?? __('N/A', 'smarty-very-simple-license-manager')); ?>" readonly /></td>
                                                        </tr>
                                                        <tr>
                                                            <td><label><?php esc_html_e('Device Type:', 'smarty-very-simple-license-manager'); ?></label></td>
                                                            <td><input type="text" name="device_type" value="<?php echo esc_html($url_data['device_type'] ?? __('N/A', 'smarty-very-simple-license-manager')); ?>" readonly /></td>
                                                        </tr>
                                                        <tr>
                                                            <td><label><?php esc_html_e('Operating System:', 'smarty-very-simple-license-manager'); ?></label></td>
                                                            <td><input type="text" name="os" value="<?php echo esc_html($url_data['os'] ?? __('N/A', 'smarty-very-simple-license-manager')); ?>" readonly /></td>
                                                        </tr>
                                                    </tbody>
                                                </table>
											</td>
										</tr>
									<?php endif; ?>
								<?php endforeach; ?>
							</tbody>
						</table>
					<?php else: ?>
						<p style="text-align: center;"><?php esc_html_e('No usage URLs available.', 'smarty-very-simple-license-manager'); ?></p>
					<?php endif; ?>
                <?php endif; ?>
            </div>

        </div> <!-- End three-column layout -->

        <?php
        // Add nonce field for security
        wp_nonce_field('smarty_vslm_save_license_meta', 'smarty_vslm_license_nonce');
    }
}

if (!function_exists('smarty_vslm_save_license_meta')) {
    /**
     * Save the license meta data with nonce verification.
     *
     * @param int $post_id The ID of the current post being saved.
     * @param WP_Post $post The current post object.
     */
    function smarty_vslm_save_license_meta($post_id, $post) {
        if ($post->post_type === 'vslm-licenses') {
            // Verify nonce for security
            if (!isset($_POST['smarty_vslm_license_nonce']) || !wp_verify_nonce($_POST['smarty_vslm_license_nonce'], 'smarty_vslm_save_license_meta')) {
                return $post_id;
            }

            // Save multi-domain setting
            $multi_domain = isset($_POST['multi_domain']) ? '1' : '0';
            update_post_meta($post_id, '_multi_domain', $multi_domain);

            if ($multi_domain !== '1') {
                // Single-domain usage
                if (isset($_POST['usage_url'])) {
                    $usage_url = esc_url_raw($_POST['usage_url']);
                    update_post_meta($post_id, '_usage_url', $usage_url);
                }
                // Delete the multi-domain usage URLs
                delete_post_meta($post_id, '_usage_urls');
            } else {
                // Multi-domain usage
                // Delete the single usage URL meta
                delete_post_meta($post_id, '_usage_url');
                // The _usage_urls meta will be managed via the REST API
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
    add_action('save_post', 'smarty_vslm_save_license_meta', 10, 2);
}

if (!function_exists('smarty_vslm_set_numeric_slug')) {
    /**
     * Set a numeric slug for licenses instead of a title-based slug.
     *
     * @param array $data The array of sanitized post data.
     * @param array $postarr The array of unsanitized post data.
     * @return array Modified post data with a numeric slug.
     */
    function smarty_vslm_set_numeric_slug($data, $postarr) {
        if ($data['post_type'] === 'vslm-licenses' && $data['post_status'] === 'publish') {
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
    add_filter('wp_insert_post_data', 'smarty_vslm_set_numeric_slug', 10, 2);
}

if (!function_exists('smarty_vslm_register_product_taxonomy')) {
    /**
     * Register the 'Products' taxonomy for licenses.
     */
    function smarty_vslm_register_product_taxonomy() {
        register_taxonomy('product', 'vslm-licenses', array(
            'labels' => array(
                'name'          => esc_html(__('Products', 'smarty-very-simple-license-manager')),
                'singular_name' => esc_html(__('Product', 'smarty-very-simple-license-manager')),
                'search_items'  => esc_html(__('Search Products', 'smarty-very-simple-license-manager')),
                'all_items'     => esc_html(__('All Products', 'smarty-very-simple-license-manager')),
                'edit_item'     => esc_html(__('Edit Product', 'smarty-very-simple-license-manager')),
                'view_item'     => esc_html(__('View Product', 'smarty-very-simple-license-manager')),
                'add_new_item'  => esc_html(__('Add New Product', 'smarty-very-simple-license-manager')),
                'new_item_name' => esc_html(__('New Product Name', 'smarty-very-simple-license-manager')),
                'menu_name'     => esc_html(__('Products', 'smarty-very-simple-license-manager')),
            ),
            'hierarchical'  => true,
            'show_ui'       => true,
            'show_in_rest'  => true,
            'query_var'     => true,
            'rewrite'       => array('slug' => 'product'),
        ));
    }
    add_action('init', 'smarty_vslm_register_product_taxonomy');
}

/////////////////////////////////////////////////////////
// Admin Bar Styling
/////////////////////////////////////////////////////////

if (!function_exists('smarty_vslm_remove_admin_bar_view_posts')) {
    /**
     * Remove the "View Posts" link from the admin bar for the License post type.
     *
     * @param WP_Admin_Bar $wp_admin_bar The WP_Admin_Bar instance.
     */
    function smarty_vslm_remove_admin_bar_view_posts($wp_admin_bar) {
        // Check if we're in the License custom post type or editing it
        global $post_type, $post;

        // Only proceed if we're in the admin area and dealing with the "vslm-licenses" post type
        if (is_admin() && ($post_type === 'vslm-licenses' || (isset($post) && $post->post_type === 'vslm-licenses'))) {
            // Remove any "View Posts" or similar links in the admin bar related to this post type
            $wp_admin_bar->remove_node('edit'); // Standard "Edit" node ID
            $wp_admin_bar->remove_node('view'); // Commonly used for "View" link in admin bar
            $wp_admin_bar->remove_node('archive'); // Possible ID for "Archive" links
            $wp_admin_bar->remove_menu('view'); // Extra attempt in case `remove_node` does not cover all cases
        }
    }
    add_action('admin_bar_menu', 'smarty_vslm_remove_admin_bar_view_posts', 999);
}

/////////////////////////////////////////////////////////
// Custom Columns in Licenses List
/////////////////////////////////////////////////////////

if (!function_exists('smarty_vslm_remove_view_link')) {
    /**
     * Remove the "View" link from the row actions for licenses in the admin list.
     *
     * @param array $actions The current row actions.
     * @param WP_Post $post The current post object.
     * @return array Modified row actions without the "View" link.
     */
    function smarty_vslm_remove_view_link($actions, $post) {
        // Check if the current post type is "license"
        if ($post->post_type === 'vslm-licenses') {
            unset($actions['view']); // Remove the "View" action
        }
        return $actions;
    }
    add_filter('post_row_actions', 'smarty_vslm_remove_view_link', 10, 2);
}

if (!function_exists('smarty_vslm_remove_quick_edit')) {
    /**
     * Remove "Quick Edit" from the row actions in the licenses list.
     *
     * @param array $actions The current row actions.
     * @param WP_Post $post The current post object.
     * @return array Modified row actions without the "Quick Edit" option.
     */
    function smarty_vslm_remove_quick_edit($actions, $post) {
        if ($post->post_type === 'vslm-licenses') {
            unset($actions['inline hide-if-no-js']); // Remove the "Quick Edit" action
        }
        return $actions;
    }
    add_filter('post_row_actions', 'smarty_vslm_remove_quick_edit', 10, 2);
}

if (!function_exists('smarty_vslm_add_license_columns')) {
    /**
     * Add custom columns for License Key, Status, Expiration Date, and User Email in the licenses list table.
     *
     * @param array $columns The current list of columns.
     * @return array Modified list of columns.
     */
    function smarty_vslm_add_license_columns($columns) {
        unset($columns['title']);
        // Define the new columns order, placing "Product" first
        $new_columns = array(
            'product'         => esc_html(__('Product', 'smarty-very-simple-license-manager')),
            'product_version' => esc_html(__('Version', 'smarty-very-simple-license-manager')),
            'license_key'     => esc_html(__('License Key', 'smarty-very-simple-license-manager')),
            'purchase_date'   => esc_html(__('Purchase Date', 'smarty-very-simple-license-manager')),
            'expiration_date' => esc_html(__('Expiration Date', 'smarty-very-simple-license-manager')),
            'client_name'     => esc_html(__('Client Name', 'smarty-very-simple-license-manager')),
            'client_email'    => esc_html(__('Client Email', 'smarty-very-simple-license-manager')),
            'usage_urls'      => esc_html(__('Usage URL(s)', 'smarty-very-simple-license-manager')),
            'license_status'  => esc_html(__('Status', 'smarty-very-simple-license-manager')),
        );

        return $new_columns;
    }
    add_filter('manage_vslm-licenses_posts_columns', 'smarty_vslm_add_license_columns');
}

if (!function_exists('smarty_vslm_fill_license_columns')) {
    /**
     * Populate the custom columns for License Key, Status, Expiration Date, and User Email.
     *
     * @param string $column The name of the column.
     * @param int $post_id The ID of the current post.
     */
    function smarty_vslm_fill_license_columns($column, $post_id) {
        if ($column === 'license_key') {
            $license_key = get_post_meta($post_id, '_license_key', true);
            $masked_key = substr($license_key, 0, 4) . '-XXXX-XXXX-XXXX';

            echo '<div class="smarty-vslm-license-key-wrapper">';
            
            // Masked key
            echo '<span class="smarty-vslm-masked-key" style="vertical-align: middle;">' . esc_html($masked_key) . '</span>';
            echo '<input type="hidden" class="smarty-vslm-full-key" value="' . esc_attr($license_key) . '" />';
        
            // Show/Hide and Copy links
            echo '<div class="smarty-vslm-key-toggle-links">';
            echo '<a href="#" class="row-actions smarty-vslm-show-key-link">Show</a>';
            echo '<a href="#" class="row-actions smarty-vslm-hide-key-link" style="display:none;">Hide</a>';
            echo '<span class="row-actions">|</span>';
            echo '<a href="#" class="row-actions smarty-vslm-copy-key-link" onclick="copyLicenseKey(this, \'' . esc_attr($license_key) . '\')">Copy</a>';
            echo '</div>';
            
            echo '</div>';
        } elseif ($column === 'license_status') {
            $status = get_post_meta($post_id, '_status', true);
            $status_text = ucfirst($status);
            
            if ($status === 'active') {
                echo '<span class="smarty-vslm-status-badge active">' . $status_text . '</span>';
            } elseif ($status === 'inactive') {
                echo '<span class="smarty-vslm-status-badge inactive">' . $status_text . '</span>';
            } elseif ($status === 'expired') {
                echo '<span class="smarty-vslm-status-badge expired">' . $status_text . '</span>';
            } else {
                echo '<span>' . $status_text . '</span>';
            }
        } elseif ($column === 'purchase_date') {
            $purchase_date = get_post_meta($post_id, '_purchase_date', true);
            $formatted_purchase_date = date('Y/m/d', strtotime($purchase_date)); // Format as YYYY/MM/DD
            echo esc_html($formatted_purchase_date);
        } elseif ($column === 'expiration_date') {
            $expiration_date = get_post_meta($post_id, '_expiration_date', true);
            $formatted_expiration_date = date('Y/m/d', strtotime($expiration_date));
            echo esc_html($formatted_expiration_date);
            
            // Calculate days left if the expiration date is valid
            $current_date = new DateTime();
            $expiration = new DateTime($expiration_date);
            $interval = $current_date->diff($expiration);
            
            if ($interval->invert === 0) { // Not expired yet
                echo '<small class="active" style="display: block;">' . $interval->days . ' days left</small>';
            } else {
                echo '<small class="inactive" style="display: block;">0 days left</small>';
            }
        } elseif ($column === 'client_name') {
            echo esc_html(get_post_meta($post_id, '_client_name', true));
        } elseif ($column === 'client_email') {
            echo esc_html(get_post_meta($post_id, '_client_email', true));
        } elseif ($column === 'usage_urls') {
            $multi_domain = get_post_meta($post_id, '_multi_domain', true);
            if ($multi_domain === '1') {
                $usage_urls = get_post_meta($post_id, '_usage_urls', true) ?: array();
                if (!empty($usage_urls)) {
                    foreach ($usage_urls as $url_data) {
                        if (is_array($url_data) && isset($url_data['site_url'])) {
                            echo '<div>' . esc_html($url_data['site_url']) . '</div>';
                        }
                    }
                } else {
                    echo '—';
                }
            } else {
                $usage_url = get_post_meta($post_id, '_usage_url', true);
                echo $usage_url ? esc_url($usage_url) : '—';
            }
        } elseif ($column === 'product') {
            // Display the product name(s)
            $product_terms = get_the_terms($post_id, 'product');
            if (!empty($product_terms) && !is_wp_error($product_terms)) {
                $product_names = wp_list_pluck($product_terms, 'name');
                echo '<b>' . esc_html(implode(', ', $product_names)) . '</b>';
            } else {
                echo '—'; // Display a dash if no product is assigned
            }
        } elseif ($column === 'product_version') {
            // Retrieve the product version from the post meta
            $product_version = get_post_meta($post_id, '_plugin_version', true);
            
            // Display the product version or a placeholder if not available
            echo $product_version ? esc_html($product_version) : esc_html(__('Not recorded', 'smarty-very-simple-license-manager'));
        }
    }
    add_action('manage_vslm-licenses_posts_custom_column', 'smarty_vslm_fill_license_columns', 10, 2);
}

if (!function_exists('smarty_vslm_custom_admin_styles')) {
    function smarty_vslm_custom_admin_styles() {
        global $post_type;

        if ('vslm-licenses' === $post_type) {
            echo '<style>
                .wp-list-table .column-product_version {
                    width: 80px; /* Adjust this width as needed */
                    text-align: center;
                }
            </style>';
        }
    }
    add_action('admin_head', 'smarty_vslm_custom_admin_styles');
}

/////////////////////////////////////////////////////////
// License Status Sorting
/////////////////////////////////////////////////////////

if (!function_exists('smarty_vslm_sortable_license_columns')) {
    /**
     * Define sortable columns for License Key and Status.
     *
     * @param array $columns The current list of sortable columns.
     * @return array Modified list of sortable columns.
     */
    function smarty_vslm_sortable_license_columns($columns) {
        $columns['license_key'] = 'license_key';
        $columns['license_status'] = 'license_status';
        return $columns;
    }
    add_filter('manage_edit-vslm-licenses_sortable_columns', 'smarty_vslm_sortable_license_columns');
}

if (!function_exists('smarty_vslm_orderby_license_columns')) {
    /**
     * Modify the query to handle sorting by license key and status.
     *
     * @param WP_Query $query The current WP_Query instance.
     */
    function smarty_vslm_orderby_license_columns($query) {
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
    add_action('pre_get_posts', 'smarty_vslm_orderby_license_columns');
}

/////////////////////////////////////////////////////////
// Cron Job for Expired License Check
/////////////////////////////////////////////////////////

if (!function_exists('smarty_vslm_schedule_cron_job')) {
    /**
     * Schedule a daily cron job to check for expired licenses.
     */
    function smarty_vslm_schedule_cron_job() {
        if (!wp_next_scheduled('smarty_vslm_license_check')) {
            wp_schedule_event(time(), 'daily', 'smarty_vslm_license_check');
        }
    }
    add_action('wp', 'smarty_vslm_schedule_cron_job');
}

if (!function_exists('smarty_vslm_check_expired_licenses')) {
    /**
     * Cron job function to mark expired licenses as expired.
     */
    function smarty_vslm_check_expired_licenses() {
        $licenses = get_posts(array('post_type' => 'vslm-licenses', 'posts_per_page' => -1));
        foreach ($licenses as $license) {
            $expiration_date = get_post_meta($license->ID, '_expiration_date', true);
            if (strtotime($expiration_date) < time()) {
                update_post_meta($license->ID, '_status', 'expired');
            }
        }
    }
    add_action('smarty_vslm_license_check', 'smarty_vslm_check_expired_licenses');
}

/////////////////////////////////////////////////////////
// Settings Page for API Key Management
/////////////////////////////////////////////////////////

if (!function_exists('smarty_vslm_settings_page')) {
    /**
     * Create settings page for API key management.
     */
    function smarty_vslm_settings_page() {
        add_options_page(
            __('License Manager | Settings', 'smarty-very-simple-license-manager'),
            __('License Manager', 'smarty-very-simple-license-manager'),
            'manage_options', 
            'smarty-vslm-settings', 
            'smarty_vslm_settings_page_html'
        );
    }
    add_action('admin_menu', 'smarty_vslm_settings_page');
}

if (!function_exists('smarty_vslm_settings_page_html')) {
    /**
     * Settings page HTML.
     */
    function smarty_vslm_settings_page_html() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            return;
        }

        // HTML
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('License Manager | Settings', 'smarty-very-simple-license-manager'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('smarty_vslm_settings'); 
                do_settings_sections('smarty_vslm_settings');
                ?>

                <!-- Warning message -->
                <div style="background-color: #fff3cd; border: 1px solid #e5d4a2; border-left: 4px solid #e5d4a2; border-radius: 3px; padding: 10px; margin-top: 20px;">
                    <p><?php esc_html(_e('The Consumer Key and Consumer Secret keys are used to authenticate API requests for the License Manager.</p><p>These keys should be generated once and not changed thereafter.</p><p>Altering them could disrupt existing API integrations that rely on these keys for secure access.', 'smarty-very-simple-license-manager')); ?></p>
                </div>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}

if (!function_exists('smarty_vslm_register_settings')) {
    /**
     * Register settings for CK_KEY and CS_KEY in License Manager.
     */
    function smarty_vslm_register_settings() {
        register_setting('smarty_vslm_settings', 'smarty_vslm_ck_key');
        register_setting('smarty_vslm_settings', 'smarty_vslm_cs_key');

        // Add General section
        add_settings_section(
            'smarty_vslm_section_general',                          // ID of the section
            __('General', 'smarty-very-simple-license-manager'),    // Title of the section
            'smarty_vslm_section_general_callback',                 // Callback function that fills the section with the desired content
            'smarty_vslm_settings'                                  // Page on which to add the section
        );

        add_settings_field(
            'smarty_vslm_ck_key', 
            __('Consumer Key', 'smarty-very-simple-license-manager'),
            'smarty_vslm_ck_key_callback', 
            'smarty_vslm_settings', 
            'smarty_vslm_section_general'
        );

        add_settings_field(
            'smarty_vslm_cs_key', 
            __('Consumer Secret', 'smarty-very-simple-license-manager'),
            'smarty_vslm_cs_key_callback', 
            'smarty_vslm_settings', 
            'smarty_vslm_section_general'
        );
    }
    add_action('admin_init', 'smarty_vslm_register_settings');
}

if (!function_exists('smarty_vslm_section_general_callback')) {
    /**
     * General section callback for the License Manager.
     */
    function smarty_vslm_section_general_callback() { ?>
        <p><?php echo esc_html(_e('General settings for the License Manager.', 'smarty-very-simple-license-manager')); ?></p>
        <hr><?php
    }
}

/////////////////////////////////////////////////////////
// AJAX Functions for CK and CS Key Generation
/////////////////////////////////////////////////////////

if (!function_exists('smarty_vslm_ck_key_callback')) {
    /**
     * Callback to display and regenerate the CK Key field.
     */
    function smarty_vslm_ck_key_callback() {
        $ck_key = get_option('smarty_vslm_ck_key'); ?>
        <input type="text" id="smarty_vslm_ck_key" name="smarty_vslm_ck_key" value="<?php echo esc_attr($ck_key); ?>" readonly />
        <button type="button" id="smarty_vslm_generate_ck_key" class="button"><?php esc_html(_e('Generate', 'smarty-very-simple-license-manager')); ?></button>
        <p class="description"><?php esc_html(_e('This Consumer Key is used for API authentication. Click "Generate" to create a new one.', 'smarty-very-simple-license-manager')); ?></p><?php
    }
}

if (!function_exists('smarty_vslm_cs_key_callback')) {
    /**
     * Callback to display and regenerate the CS Key field.
     */
    function smarty_vslm_cs_key_callback() {
        $cs_key = get_option('smarty_vslm_cs_key'); ?>
        <input type="text" id="smarty_vslm_cs_key" name="smarty_vslm_cs_key" value="<?php echo esc_attr($cs_key); ?>" readonly />
        <button type="button" id="smarty_vslm_generate_cs_key" class="button"><?php esc_html(_e('Generate', 'smarty-very-simple-license-manager')); ?></button>
        <p class="description"><?php esc_html(_e('This Consumer Secret is used as a secret key for API requests. Click "Generate" to create a new one.', 'smarty-very-simple-license-manager')); ?></p><?php
    }
}

/////////////////////////////////////////////////////////
// REST API for License Status Check
/////////////////////////////////////////////////////////

if (!function_exists('smarty_vslm_register_license_status_endpoint')) {
    /**
     * Register REST API endpoint for license status check.
     */
    function smarty_vslm_register_license_status_endpoint() {
        register_rest_route('smarty-vslm/v1', '/check-license', array(
            'methods'             => 'GET',
            'callback'            => 'smarty_vslm_check_license_status',
            'permission_callback' => 'smarty_vslm_basic_auth_permission_check',
        ));
    }
    add_action('rest_api_init', 'smarty_vslm_register_license_status_endpoint');
}

if (!function_exists('smarty_vslm_basic_auth_permission_check')) {
    /**
     * Permission callback for Basic Auth.
     *
     * @return bool True if authentication is successful, false otherwise.
     */
    function smarty_vslm_basic_auth_permission_check() {
        $headers = getallheaders();
        if (isset($headers['Authorization'])) {
            $auth_header = $headers['Authorization'];
            if (strpos($auth_header, 'Basic ') === 0) {
                $encoded_credentials = substr($auth_header, 6);
                $decoded_credentials = base64_decode($encoded_credentials);
                list($provided_ck_key, $provided_cs_key) = explode(':', $decoded_credentials, 2);

                // Retrieve the stored keys
                $stored_ck_key = get_option('smarty_vslm_ck_key');
                $stored_cs_key = get_option('smarty_vslm_cs_key');

                // Validate credentials
                if ($provided_ck_key === $stored_ck_key && $provided_cs_key === $stored_cs_key) {
                    return true;
                }
            }
        }
        return false;
    }
}

if (!function_exists('smarty_vslm_check_license_status')) {
    /**
     * Callback for the REST API endpoint to check license status.
     *
     * @param WP_REST_Request $request The REST request object.
     * @return WP_REST_Response The REST response with license status, expiration date, usage URL, and WordPress version.
     */
    function smarty_vslm_check_license_status(WP_REST_Request $request) {
        $license_key = $request->get_param('license_key');
        $site_url = $request->get_param('site_url');
        $wp_version = $request->get_param('wp_version');
        $web_server = $request->get_param('web_server');
        $server_ip = $request->get_param('server_ip');
        $php_version = $request->get_param('php_version');
        $plugin_name = $request->get_param('plugin_name');
        $plugin_version = $request->get_param('plugin_version');
        $user_ip = $request->get_param('user_ip');
        $browser = $request->get_param('browser');
        $device_type = $request->get_param('device_type');
        $os = $request->get_param('os');

        // Find the license by key
        $license_posts = get_posts(array(
            'post_type' => 'vslm-licenses',
            'meta_query' => array(
                array(
                    'key' => '_license_key',
                    'value' => $license_key,
                    'compare' => '='
                )
            )
        ));

        if (empty($license_posts)) {
			return new WP_REST_Response([
				'status' => 'rest_no_route',
				'message' => 'The requested route does not exist.',
			], 404);
		}

        $license_id = $license_posts[0]->ID;
        $multi_domain = get_post_meta($license_id, '_multi_domain', true);

        if (!empty($site_url) && filter_var($site_url, FILTER_VALIDATE_URL)) {
            if ($multi_domain === '1') {
				// Multi-domain handling
				$usage_urls = get_post_meta($license_id, '_usage_urls', true) ?: [];

                // Check if the URL already exists
                $existing_key = array_search($site_url, array_column($usage_urls, 'site_url'));

				$url_data = [
					'site_url'       => $site_url,
					'wp_version'     => $wp_version,
					'plugin_version' => $plugin_version,
					'web_server'     => $web_server,
					'server_ip'      => $server_ip,
					'php_version'    => $php_version,
					'user_ip'        => $user_ip,
					'browser'        => $browser,
					'device_type'    => $device_type,
					'os'             => $os,
				];

				if ($existing_key !== false) {
                    // Update existing entry
                    $usage_urls[$existing_key] = $url_data;
                } else {
                    // Add new entry
                    $usage_urls[] = $url_data;
                }

				update_post_meta($license_id, '_usage_urls', $usage_urls);
			} else {
                // Single-domain usage
                $existing_usage_url = get_post_meta($license_id, '_usage_url', true);
                
				if (empty($existing_usage_url) || $existing_usage_url === $site_url) {
                    update_post_meta($license_id, '_usage_url', esc_url_raw($site_url));
                } else {
                    return new WP_REST_Response([
                        'status'  => 'error',
                        'message' => 'License already activated on another domain.',
                    ], 403);
                }
            }
        }

        if (!empty($wp_version)) {
            update_post_meta($license_id, '_wp_version', sanitize_text_field($wp_version));
        }

        if (!empty($plugin_name)) {
            update_post_meta($license_id, '_plugin_name', sanitize_text_field($plugin_name));
        }

        if (!empty($plugin_version)) {
            update_post_meta($license_id, '_plugin_version', sanitize_text_field($plugin_version));
        }

        if (!empty($web_server)) {
            update_post_meta($license_id, '_web_server', sanitize_text_field($web_server));
        }

        if (!empty($server_ip)) {
            update_post_meta($license_id, '_server_ip', sanitize_text_field($server_ip));
        }

        if (!empty($php_version)) {
            update_post_meta($license_id, '_php_version', sanitize_text_field($php_version));
        }

        if (!empty($user_ip)) {
            update_post_meta($license_id, '_user_ip', sanitize_text_field($user_ip));
        }

        if (!empty($browser)) {
            update_post_meta($license_id, '_browser', sanitize_text_field($browser));
        }
        
        if (!empty($device_type)) {
            update_post_meta($license_id, '_device_type', sanitize_text_field($device_type));
        }

        if (!empty($os)) {
            update_post_meta($license_id, '_os', sanitize_text_field($os));
        }

        // Retrieve license status, expiration date, usage URL, WP version, Web server and Server IP
        $license_status = get_post_meta($license_id, '_status', true);
        $expiration_date = get_post_meta($license_id, '_expiration_date', true);
        $stored_wp_version = get_post_meta($license_id, '_wp_version', true);
        $stored_web_server = get_post_meta($license_id, '_web_server', true);
        $stored_server_ip = get_post_meta($license_id, '_server_ip', true);
        $stored_php_version = get_post_meta($license_id, '_php_version', true);
        $stored_plugin_name = get_post_meta($license_id, '_plugin_name', true);
        $stored_plugin_version = get_post_meta($license_id, '_plugin_version', true);
        $stored_user_ip = get_post_meta($license_id, '_user_ip', true);
        $stored_browser = get_post_meta($license_id, '_browser', true);
        $stored_device_type = get_post_meta($license_id, '_device_type', true);
        $stored_os = get_post_meta($license_id, '_os', true);

        $response_data = array(
            'status'           => $license_status,
            'expiration_date'  => $expiration_date,
            'multi_domain'     => $multi_domain === '1',
            'wp_version'       => $stored_wp_version,
            'web_server'       => $stored_web_server,
            'server_ip'        => $stored_server_ip,
            'php_version'      => $stored_php_version,
            'plugin_name'      => $stored_plugin_name,
            'plugin_version'   => $stored_plugin_version,
            'user_ip'          => $stored_user_ip,
            'browser'          => $stored_browser,
            'device_type'      => $stored_device_type,
            'os'               => $stored_os,
        );

        if ($multi_domain === '1') {
            $response_data['usage_urls'] = get_post_meta($license_id, '_usage_urls', true) ?: array();
        } else {
            $response_data['usage_url'] = get_post_meta($license_id, '_usage_url', true);
        }

        return new WP_REST_Response([
			'status' => $license_status ? $license_status : 'inactive',
			'expiration_date' => $expiration_date,
			'message' => 'License status retrieved successfully.',
		], 200);
    }
}

if (!function_exists('smarty_vslm_license_sortable_columns')) {
    /**
     * Make the Status column sortable in the admin.
     *
     * @param array $columns List of sortable columns.
     * @return array Updated sortable columns.
     */
    function smarty_vslm_license_sortable_columns($columns) {
        $columns['license_status'] = 'license_status';
        return $columns;
    }
    add_filter('manage_edit-license_sortable_columns', 'smarty_vslm_license_sortable_columns');
}

if (!function_exists('smarty_vslm_license_orderby_status')) {
    /**
     * Sort the licenses by status in the admin.
     *
     * @param WP_Query $query The current WP_Query object.
     */
    function smarty_vslm_license_orderby_status($query) {
        if (!is_admin()) {
            return;
        }

        $orderby = $query->get('orderby');
        if ('license_status' === $orderby) {
            $query->set('meta_key', '_status');
            $query->set('orderby', 'meta_value');
        }
    }
    add_action('pre_get_posts', 'smarty_vslm_license_orderby_status');
}

if (!function_exists('smarty_vslm_generate_ck_key')) {
    /**
     * AJAX handler to generate a CK key.
     */
    function smarty_vslm_generate_ck_key() {
        $ck_key = 'ck_' . bin2hex(random_bytes(20)); // Generate a CK key
        update_option('smarty_vslm_ck_key', $ck_key);
        wp_send_json_success($ck_key);
    }
    add_action('wp_ajax_generate_ck_key', 'smarty_vslm_generate_ck_key');
}

if (!function_exists('smarty_vslm_generate_cs_key')) {
    /**
     * AJAX handler to generate a CS key.
     */
    function smarty_vslm_generate_cs_key() {
        $cs_key = 'cs_' . bin2hex(random_bytes(20)); // Generate a CS key
        update_option('smarty_vslm_cs_key', $cs_key);
        wp_send_json_success($cs_key);
    }
    add_action('wp_ajax_generate_cs_key', 'smarty_vslm_generate_cs_key');
}
