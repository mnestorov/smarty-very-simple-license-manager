<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two hooks for how to enqueue 
 * the admin-specific stylesheet (CSS) and JavaScript code.
 *
 * @link       https://github.com/mnestorov/smarty-very-simple-license-manager
 * @since      1.0.1
 *
 * @package    Smarty_Very_Simple_License_Manager
 * @subpackage Smarty_Very_Simple_License_Manager/admin
 * @author     Smarty Studio | Martin Nestorov
 */
class Smarty_Vslm_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.1
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.1
	 * @access   private
	 * @var      string    $version         The current version of this plugin.
	 */
	private $version;

	/**
	 * Instance of Smarty_Vslm_Activity_Logging.
	 * 
	 * @since    1.0.1
	 * @access   private
	 */
	private $activity_logging;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.1
	 * @param    string    $plugin_name     The name of this plugin.
	 * @param    string    $version         The version of this plugin.
	 */
	public function __construct($plugin_name, $version) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;

		// Include and instantiate the Activity Logging class
		$this->activity_logging = new Smarty_Vslm_Activity_Logging();
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.1
	 */
	public function enqueue_styles() {
		/**
		 * This function enqueues custom CSS for the plugin settings in WordPress admin.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Smarty_Vslm_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Smarty_Vslm_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/smarty-vslm-admin.css', array(), $this->version, false);
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.1
	 */
	public function enqueue_scripts($hook) {
		/**
		 * This function enqueues custom JavaScript for the plugin settings in WordPress admin.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Smarty_Vslm_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Smarty_Vslm_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

        global $post_type;

        // Check if we're on the License Manager settings page
        if ($hook === 'settings_page_smarty-vslm-settings') {
            // Enqueue AJAX script for the settings page
            wp_enqueue_script('smarty-vslm-ajax', plugin_dir_url(__FILE__) . 'js/smarty-vslm-ajax.js', array('jquery'), time(), true);

            // Localize AJAX URL for the JavaScript
            wp_localize_script(
                'smarty-vslm-ajax',
                'smartyVerySimpleLicenseManager',
                array(
                    'ajaxUrl' => admin_url('admin-ajax.php'),
                    'siteUrl' => site_url(),
                    'nonce'   => wp_create_nonce('smarty_vslm_license_nonce'),
                )
            );
        }

        if ($post_type === 'vslm-licenses' || get_post_type() === 'vslm-licenses') {
            // Enqueue JS files for license post type
            wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/smarty-vslm-admin.js', array(), $this->version, false);
            wp_enqueue_script('smarty-vslm-json', plugin_dir_url(__FILE__) . 'js/smarty-vslm-json.js', array(), time(), true);
        }
	}

    /**
     * Register the custom dashboard widget.
     * 
     * @since    1.0.1
     */
    public function vslm_add_dashboard_widget() {
        wp_add_dashboard_widget(
            'smarty_vslm_dashboard_widget',                                         // Widget ID
            __('License Manager Overview', 'smarty-very-simple-license-manager'),   // Widget Title
            array($this, 'vslm_dashboard_widget_render_cb')                         // Callback function to display content
        );
    }

    /**
     * Render the content of the custom dashboard widget with a centered icon and link.
     * 
     * @since    1.0.1
     */
    public function vslm_dashboard_widget_render_cb() {
        // Query for licenses and count statuses
        $total_count = wp_count_posts('vslm-licenses')->publish; // Get total published licenses
        $active_count = smarty_vslm_get_license_count_by_status('active');
        $inactive_count = smarty_vslm_get_license_count_by_status('inactive');
        $expired_count = smarty_vslm_get_license_count_by_status('expired'); ?>

        <div style="padding: 5px;">
            <h3 style="margin: 0; display: flex; align-items: center; gap: 5px;">
                <a href="edit.php?post_type=vslm-licenses" style="font-size: 1.2em; color: #135e96; text-decoration: none;"><?php echo $total_count; ?> Licenses</a>
            </h3>

            <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
                <thead>
                    <tr style="background-color: #f5f5f5; border-bottom: 1px solid #ddd;">
                        <th style="border-top: 1px solid #ddd; padding: 8px; text-align: left;"><?php echo __('Status', 'smarty-very-simple-license-manager'); ?></th>
                        <th style="border-top: 1px solid #ddd; padding: 8px; text-align: center;"><?php echo __('Count', 'smarty-very-simple-license-manager'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="border-top: 1px solid #ddd; padding: 8px; color: #28a745; font-weight: bold;"><?php echo __('Active Licenses', 'smarty-very-simple-license-manager'); ?></td>
                        <td style="border-top: 1px solid #ddd; padding: 8px; text-align: center; font-weight: bold;"><?php echo $active_count; ?></td>
                    </tr>
                    <tr>
                        <td style="border-top: 1px solid #ddd; padding: 8px; color: #dc3545; font-weight: bold;"><?php echo __('Inactive Licenses', 'smarty-very-simple-license-manager'); ?></td>
                        <td style="border-top: 1px solid #ddd; padding: 8px; text-align: center; font-weight: bold;"><?php echo  $inactive_count; ?></td>
                    </tr>
                    <tr>
                        <td style="border-top: 1px solid #ddd; padding: 8px; color: #427eab; font-weight: bold;"><?php echo __('Expired Licenses', 'smarty-very-simple-license-manager'); ?></td>
                        <td style="border-top: 1px solid #ddd; padding: 8px; text-align: center; font-weight: bold;"><?php echo $expired_count; ?></td>
                    </tr>
                </tbody>
            </table>
        </div><?php
    }

    /**
     * Register the custom post type for managing licenses.
     * 
     * @since    1.0.1
     */
    public function vslm_register_license_post_type() {
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

    /**
     * Customize the update messages for the License custom post type.
     *
     * @since        1.0.1
     * @param array  $messages Default update messages.
     * @return array Modified update messages.
     */
    public function vslm_license_post_updated_messages($messages) {
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

    /**
     * Add custom meta boxes for license details in the license edit screen, with status dot after the title.
     * 
     * @since    1.0.1
     */
    public function vslm_add_license_meta_boxes() {
        add_meta_box(
            'license_details',
            array($this, 'vslm_license_meta_box_title'), // Set title with dynamic status dot
            array($this, 'vslm_license_details_cb'),
            'vslm-licenses',
            'normal',
            'default'
        );
    }

    /**
     * Generate the title for the license meta box with a colored status dot.
     *
     * @since    1.0.1
     * @return string The meta box title with a status dot.
     */
    public function vslm_license_meta_box_title() {
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

    /**
     * Add a meta box for displaying the JSON response from the plugin status endpoint.
     * 
     * @since    1.0.1
     */
    function vslm_add_json_response_meta_box() {
        add_meta_box(
            'smarty_vslm_json_response',
            __('Product Status (JSON)', 'smarty-very-simple-license-manager'),
            array($this, 'vslm_json_response_meta_box_cb'),
            'vslm-licenses',
            'normal',
            'default'
        );
    }

    /**
     * Callback for the Plugin Status JSON Response meta box.
     *
     * @since    1.0.1
     * @param WP_Post $post The current post object.
     */
    public function vslm_json_response_meta_box_cb($post) {
        // Retrieve meta data
        $multi_domain = get_post_meta($post->ID, '_multi_domain', true);
        $usage_url = get_post_meta($post->ID, '_usage_url', true);
        $usage_urls = get_post_meta($post->ID, '_usage_urls', true) ?: [];
        $plugin_name = get_post_meta($post->ID, '_plugin_name', true);
		$json_response_info = __('This is the product status JSON response from client site.', 'smarty-very-simple-license-manager');

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
            <div class="smarty-vslm-json-response smarty-vslm-json-container" data-json-endpoint="<?php echo esc_url($endpoint); ?>">
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
                    <div class="smarty-vslm-json-response smarty-vslm-json-container" data-json-endpoint="<?php echo esc_url($endpoint); ?>">
                        <p class="success"><?php echo __('Loading JSON response...', 'smarty-very-simple-license-manager'); ?></p>
                    </div>
                    <?php
                } else {
                    echo '<p class="error">' . __('Invalid or missing URL in multi-domain configuration.', 'smarty-very-simple-license-manager') . '</p>';
                }
            }
        }
    }

    /**
     * Callback function to render the license details meta box.
     *
     * @since    1.0.1
     * @param WP_Post $post The current post object.
     */
    public function vslm_license_details_cb($post) {
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
        $wp_version = get_post_meta($post->ID, '_wp_version', true) ?: esc_html(__('Not recorded yet', 'smarty-very-simple-license-manager'));
		
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

        <div class="smarty-vslm-two-col">
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
												<button type="button" class="button smarty-vslm-generate-key-button" onclick="<?php echo Smarty_Vslm_Activity_Logging::vslm_add_activity_log("License key generated for License #{$post->ID}"); ?>"><?php esc_html(_e('Generate Key', 'smarty-very-simple-license-manager')); ?></button>
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
            </div>
			
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
												<th colspan="2" style="text-align: center;"><?php echo esc_html($usage_url ?: __('N/A', 'smarty-very-simple-license-manager')); ?></th>
											</tr>
										</thead>
                                        <tbody>
                                            <tr>
                                                <td><label><?php esc_html(_e('Plugin Name', 'smarty-very-simple-license-manager')); ?></label></td>
                                                <td><input type="text" name="plugin_name" value="<?php echo esc_html($plugin_name); ?>" readonly /></td>
                                            </tr>
                                            <tr>
                                                <td><label><?php esc_html_e('Plugin Version', 'smarty-very-simple-license-manager'); ?></label></td>
                                                <td><input type="text" name="plugin_version" value="<?php echo esc_html($plugin_version); ?>" readonly /></td>
                                            </tr>
                                            <tr>
                                                <td><label><?php esc_html_e('WP Version', 'smarty-very-simple-license-manager'); ?></label></td>
                                                <td><input type="text" name="wp_version" value="<?php echo esc_html($wp_version); ?>" readonly /></td>
                                            </tr>
                                            <tr>
                                                <td><label><?php esc_html_e('Web Server', 'smarty-very-simple-license-manager'); ?></label></td>
                                                <td><input type="text" name="web_server" value="<?php echo esc_html($web_server); ?>" readonly /></td>
                                            </tr>
                                            <tr>
                                                <td><label><?php esc_html_e('Server IP', 'smarty-very-simple-license-manager'); ?></label></td>
                                                <td><input type="text" name="server_ip" value="<?php echo esc_html($server_ip); ?>" readonly /></td>
                                            </tr>
                                            <tr>
                                                <td><label><?php esc_html_e('PHP Version', 'smarty-very-simple-license-manager'); ?></label></td>
                                                <td><input type="text" name="php_version" value="<?php echo esc_html($php_version); ?>" readonly /></td>
                                            </tr>
                                            <tr>
                                                <td><label><?php esc_html_e('User IP', 'smarty-very-simple-license-manager'); ?></label></td>
                                                <td><input type="text" name="user_ip" value="<?php echo esc_html($user_ip); ?>" readonly /></td>
                                            </tr>
                                            <tr>
                                                <td><label><?php esc_html_e('Browser', 'smarty-very-simple-license-manager'); ?></label></td>
                                                <td><input type="text" name="browser" value="<?php echo esc_html($browser); ?>" readonly /></td>
                                            </tr>
                                            <tr>
                                                <td><label><?php esc_html_e('Device Type', 'smarty-very-simple-license-manager'); ?></label></td>
                                                <td><input type="text" name="device_type" value="<?php echo esc_html($device_type); ?>" readonly /></td>
                                            </tr>
                                            <tr>
                                                <td><label><?php esc_html_e('Operating System', 'smarty-very-simple-license-manager'); ?></label></td>
                                                <td><input type="text" name="os" value="<?php echo esc_html($os); ?>" readonly /></td>
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
															<th colspan="2" style="text-align: center;"><?php echo esc_html($url_data['site_url']); ?></th>
														</tr>
													</thead>
                                                    <tbody>
														<tr>
															<td><label><?php esc_html(_e('Plugin Name', 'smarty-very-simple-license-manager')); ?></label></td>
															<td><input type="text" name="plugin_name" value="<?php echo esc_html($plugin_name); ?>" readonly /></td>
														</tr>
                                                        <tr>
                                                            <td><label><?php esc_html_e('Plugin Version:', 'smarty-very-simple-license-manager'); ?></label></td>
                                                            <td><input type="text" name="plugin_version" value="<?php echo esc_html($url_data['plugin_version']); ?>" readonly /></td>
                                                        </tr>
                                                        <tr>
                                                            <td><label><?php esc_html_e('WP Version:', 'smarty-very-simple-license-manager'); ?></label></td>
                                                            <td><input type="text" name="wp_version" value="<?php echo esc_html($url_data['wp_version']); ?>" readonly /></td>
                                                        </tr>
                                                        <tr>
                                                            <td><label><?php esc_html_e('Web Server:', 'smarty-very-simple-license-manager'); ?></label></td>
                                                            <td><input type="text" name="web_server" value="<?php echo esc_html($url_data['web_server']); ?>" readonly /></td>
                                                        </tr>
                                                        <tr>
                                                            <td><label><?php esc_html_e('Server IP:', 'smarty-very-simple-license-manager'); ?></label></td>
                                                            <td><input type="text" name="server_ip" value="<?php echo esc_html($url_data['server_ip']); ?>" readonly /></td>
                                                        </tr>
                                                        <tr>
                                                            <td><label><?php esc_html_e('PHP Version:', 'smarty-very-simple-license-manager'); ?></label></td>
                                                            <td><input type="text" name="php_version" value="<?php echo esc_html($url_data['php_version']); ?>" readonly /></td>
                                                        </tr>
                                                        <tr>
                                                            <td><label><?php esc_html_e('User IP:', 'smarty-very-simple-license-manager'); ?></label></td>
                                                            <td><input type="text" name="user_ip" value="<?php echo esc_html($url_data['user_ip']); ?>" readonly /></td>
                                                        </tr>
                                                        <tr>
                                                            <td><label><?php esc_html_e('Browser:', 'smarty-very-simple-license-manager'); ?></label></td>
                                                            <td><input type="text" name="browser" value="<?php echo esc_html($url_data['browser']); ?>" readonly /></td>
                                                        </tr>
                                                        <tr>
                                                            <td><label><?php esc_html_e('Device Type:', 'smarty-very-simple-license-manager'); ?></label></td>
                                                            <td><input type="text" name="device_type" value="<?php echo esc_html($url_data['device_type']); ?>" readonly /></td>
                                                        </tr>
                                                        <tr>
                                                            <td><label><?php esc_html_e('Operating System:', 'smarty-very-simple-license-manager'); ?></label></td>
                                                            <td><input type="text" name="os" value="<?php echo esc_html($url_data['os']); ?>" readonly /></td>
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
        </div>

        <?php
        // Add nonce field for security
        wp_nonce_field('smarty_vslm_save_license_meta', 'smarty_vslm_license_nonce');
    }

    /**
     * Save the license meta data with nonce verification.
     *
     * @since    1.0.1
     * @param int $post_id The ID of the current post being saved.
     * @param WP_Post $post The current post object.
     */
    public function vslm_save_license_meta($post_id, $post) {
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

            $this->activity_loggingvslm_add_activity_log('License key updated for License #' . $post_id . ': ' . $license_key);

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

    /**
     * Set a numeric slug for licenses instead of a title-based slug.
     *
     * @since    1.0.1
     * @param array $data The array of sanitized post data.
     * @param array $postarr The array of unsanitized post data.
     * @return array Modified post data with a numeric slug.
     */
    public function vslm_set_numeric_slug($data, $postarr) {
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

    /**
     * Register the 'Products' taxonomy for licenses.
     * 
     * @since    1.0.1
     */
    public function vslm_register_product_taxonomy() {
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

    /**
     * Remove the "View Posts" link from the admin bar for the License post type.
     *
     * @since    1.0.1
     * @param WP_Admin_Bar $wp_admin_bar The WP_Admin_Bar instance.
     */
    public function vslm_remove_admin_bar_view_posts($wp_admin_bar) {
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

    /**
     * Remove the "View" link from the row actions for licenses in the admin list.
     *
     * @since    1.0.1
     * @param array $actions The current row actions.
     * @param WP_Post $post The current post object.
     * @return array Modified row actions without the "View" link.
     */
    function vslm_remove_view_link($actions, $post) {
        // Check if the current post type is "license"
        if ($post->post_type === 'vslm-licenses') {
            unset($actions['view']); // Remove the "View" action
        }
        return $actions;
    }

    /**
     * Remove "Quick Edit" from the row actions in the licenses list.
     *
     * @since    1.0.1
     * @param array $actions The current row actions.
     * @param WP_Post $post The current post object.
     * @return array Modified row actions without the "Quick Edit" option.
     */
    function vslm_remove_quick_edit($actions, $post) {
        if ($post->post_type === 'vslm-licenses') {
            unset($actions['inline hide-if-no-js']); // Remove the "Quick Edit" action
        }
        return $actions;
    }

    /**
     * Add custom columns for License Key, Status, Expiration Date, and User Email in the licenses list table.
     *
     * @since    1.0.1
     * @param array $columns The current list of columns.
     * @return array Modified list of columns.
     */
    public function vslm_add_license_columns($columns) {
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

    /**
     * Populate the custom columns for License Key, Status, Expiration Date, and User Email.
     *
     * @since    1.0.1
     * @param string $column The name of the column.
     * @param int $post_id The ID of the current post.
     */
    public function vslm_fill_license_columns($column, $post_id) {
        if ($column === 'license_key') {
            $license_key = get_post_meta($post_id, '_license_key', true);
            $masked_key = substr($license_key, 0, 4) . '-XXXX-XXXX-XXXX'; ?>

            <div class="smarty-vslm-license-key-wrapper">
            
                <!-- Masked key -->
                <span class="smarty-vslm-masked-key" style="vertical-align: middle;"><?php echo esc_html($masked_key); ?></span>
                <input type="hidden" class="smarty-vslm-full-key" value="<?php echo esc_attr($license_key); ?>" />
            
                <!-- Show/Hide and Copy links -->
                <div class="smarty-vslm-key-toggle-links">
                    <a href="#" class="row-actions smarty-vslm-show-key-link"><?php echo esc_html(__('Show', 'smarty-very-simple-license-manager')); ?></a>
                    <a href="#" class="row-actions smarty-vslm-hide-key-link" style="display:none;"><?php echo esc_html(__('Hide', 'smarty-very-simple-license-manager')); ?></a>
                    <span class="row-actions">|</span>
                    <a href="#" class="row-actions smarty-vslm-copy-key-link" data-license-key="<?php echo esc_attr($license_key); ?>"><?php echo esc_html(__('Copy', 'smarty-very-simple-license-manager')); ?></a>
                </div>
            </div><?php
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

    /**
     * Define sortable columns for License Key and Status.
     *
     * @since    1.0.1
     * @param array $columns The current list of sortable columns.
     * @return array Modified list of sortable columns.
     */
    public function vslm_sortable_license_columns($columns) {
        $columns['license_key'] = 'license_key';
        $columns['license_status'] = 'license_status';
        return $columns;
    }

    /**
     * Modify the query to handle sorting by license key and status.
     *
     * @since    1.0.1
     * @param WP_Query $query The current WP_Query instance.
     */
    public function vslm_orderby_license_columns($query) {
        if (!is_admin()) {
            return;
        }

        if ($query->get('orderby') === 'license_key') {
            $query->set('meta_key', '_license_key');
            $query->set('orderby', 'meta_value');
        }

        if ($query->get('orderby') === 'license_status') {
            $query->set('meta_key', '_status');
            $query->set('orderby', 'meta_value');
        }
    }

    /**
     * @since    1.0.1
     */
    public function vslm_custom_admin_styles() {
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

	/**
	 * Adds an options page for the plugin in the WordPress admin menu.
	 * 
	 * @since    1.0.1
	 */
	public function vslm_add_settings_page() {
        add_submenu_page(
            'options-general.php',
            __('License Manager | Settings', 'smarty-very-simple-license-manager'),
            __('License Manager', 'smarty-very-simple-license-manager'),
            'manage_options', 
            'smarty-vslm-settings', 
            array($this, 'vslm_display_settings_page')
        );
	}

    /**
	 * @since    1.0.0
	 */
	private function vslm_get_settings_tabs() {
		$tabs = array(
			'general' 		   => __('General', 'smarty-very-simple-license-manager'),
			'activity-logging' => __('Activity & Logging', 'smarty-very-simple-license-manager'),
		);
		
		return $tabs;
	}

	/**
	 * Outputs the HTML for the settings page.
	 * 
	 * @since    1.0.1
	 */
	public function vslm_display_settings_page() {
		if (!current_user_can('manage_options')) {
			return;
		}

        $current_tab = isset($_GET['tab']) ? $_GET['tab'] : 'general';
		$tabs = $this->vslm_get_settings_tabs();

		// Define the path to the external file
		$partial_file = plugin_dir_path(__FILE__) . 'partials/smarty-vslm-admin-display.php';

		if (file_exists($partial_file) && is_readable($partial_file)) {
			include_once $partial_file;
		} else {
			_vslm_write_logs("Unable to include: '$partial_file'");
		}
	}

	/**
	 * Initializes the plugin settings by registering the settings, sections, and fields.
	 *
	 * @since    1.0.1
	 */
	public function vslm_settings_init() {
        // Check if the settings were saved and set a transient
		if (isset($_GET['settings-updated']) && $_GET['settings-updated']) {
			set_transient('smarty_vslm_settings_updated', 'yes', 5);
		}

		register_setting('smarty_vslm_options_general', 'smarty_vslm_ck_key');
        register_setting('smarty_vslm_options_general', 'smarty_vslm_cs_key');

        // Add General section
        add_settings_section(
            'smarty_vslm_section_general',                          // ID of the section
            __('General', 'smarty-very-simple-license-manager'),    // Title of the section
            array($this, 'vslm_section_general_cb'),                // Callback function that fills the section with the desired content
            'smarty_vslm_options_general'                           // Page on which to add the section
        );

        add_settings_field(
            'smarty_vslm_ck_key', 
            __('Consumer Key', 'smarty-very-simple-license-manager'),
            array($this, 'vslm_ck_key_cb'), 
            'smarty_vslm_options_general', 
            'smarty_vslm_section_general'
        );

        add_settings_field(
            'smarty_vslm_cs_key', 
            __('Consumer Secret', 'smarty-very-simple-license-manager'),
            array($this, 'vslm_cs_key_cb'), 
            'smarty_vslm_options_general', 
            'smarty_vslm_section_general'
        );
	}

    /**
     * General section callback for the License Manager.
     * 
     * @since    1.0.1
     */
    public function vslm_section_general_cb() { ?>
        <p><?php echo esc_html(_e('General settings for the License Manager.', 'smarty-very-simple-license-manager')); ?></p><?php
    }

    /**
     * Callback to display and regenerate the CK Key field.
     * 
     * @since    1.0.1
     */
    public function vslm_ck_key_cb() {
        $ck_key = get_option('smarty_vslm_ck_key'); ?>
        <input type="text" id="smarty_vslm_ck_key" name="smarty_vslm_ck_key" value="<?php echo esc_attr($ck_key); ?>" readonly />
        <button type="button" id="smarty_vslm_generate_ck_key" class="button"><?php esc_html(_e('Generate', 'smarty-very-simple-license-manager')); ?></button>
        <p class="description"><?php esc_html(_e('This Consumer Key is used for API authentication. Click "Generate" to create a new one.', 'smarty-very-simple-license-manager')); ?></p><?php
    }

    /**
     * Callback to display and regenerate the CS Key field.
     * 
     * @since    1.0.1
     */
    public function vslm_cs_key_cb() {
        $cs_key = get_option('smarty_vslm_cs_key'); ?>
        <input type="text" id="smarty_vslm_cs_key" name="smarty_vslm_cs_key" value="<?php echo esc_attr($cs_key); ?>" readonly />
        <button type="button" id="smarty_vslm_generate_cs_key" class="button"><?php esc_html(_e('Generate', 'smarty-very-simple-license-manager')); ?></button>
        <p class="description"><?php esc_html(_e('This Consumer Secret is used as a secret key for API requests. Click "Generate" to create a new one.', 'smarty-very-simple-license-manager')); ?></p><?php
    }

    /**
     * AJAX handler to generate a CK key.
     * 
     * @since    1.0.1
     */
    public function vslm_generate_ck_key() {
        $ck_key = 'ck_' . bin2hex(random_bytes(20)); // Generate a CK key
        update_option('smarty_vslm_ck_key', $ck_key);
        $this->activity_logging->vslm_add_activity_log('New CK key generated: ' . $ck_key);
        wp_send_json_success($ck_key);
    }
    
    /**
     * AJAX handler to generate a CS key.
     * 
     * @since    1.0.1
     */
    public function vslm_generate_cs_key() {
        $cs_key = 'cs_' . bin2hex(random_bytes(20)); // Generate a CS key
        update_option('smarty_vslm_cs_key', $cs_key);
        $this->activity_logging->vslm_add_activity_log('New CS key generated: ' . $cs_key);
        wp_send_json_success($cs_key);
    }
    
    /**
     * Register REST API endpoint for license status check.
     * 
     * @since    1.0.1
     */
    public function vslm_register_license_status_endpoint() {
        register_rest_route('smarty-vslm/v1', '/check-license', array(
            'methods'             => 'GET',
            'callback'            => array($this, 'vslm_check_license_status_cb'),
            'permission_callback' => array($this, 'vslm_basic_auth_permission_check_cb'),
        ));
    }

    /**
     * Permission callback for Basic Auth.
     *
     * @since    1.0.1
     * @return bool True if authentication is successful, false otherwise.
     */
    public function vslm_basic_auth_permission_check_cb() {
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

    /**
     * Callback for the REST API endpoint to check license status.
     *
     * @since    1.0.1
     * @param WP_REST_Request $request The REST request object.
     * @return WP_REST_Response The REST response with license status, expiration date, usage URL, and WordPress version.
     */
    public function vslm_check_license_status_cb(WP_REST_Request $request) {
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
        $this->activity_logging->vslm_add_activity_log('License #' . $license_id . ' successfully activated on site: ' . $site_url);
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

    /**
     * Schedule a daily cron job to check for expired licenses.
     * 
     * @since    1.0.1
     */
    public function vslm_schedule_cron_job() {
        if (!wp_next_scheduled('vslm_license_check')) {
            wp_schedule_event(time(), 'daily', 'vslm_license_check');
        }
    }
    
    /**
     * Cron job function to mark expired licenses as expired.
     * 
     * @since    1.0.1
     */
    public function vslm_check_expired_licenses() {
        $licenses = get_posts(array('post_type' => 'vslm-licenses', 'posts_per_page' => -1));
        foreach ($licenses as $license) {
            $expiration_date = get_post_meta($license->ID, '_expiration_date', true);
            if (strtotime($expiration_date) < time()) {
                update_post_meta($license->ID, '_status', 'expired');
                $this->activity_logging->vslm_add_activity_log('License #' . $license->ID . ' marked as expired.');
            }
        }
    }
}
