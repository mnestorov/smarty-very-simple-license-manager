<?php

/**
 * The Activity Logging-specific functionality of the plugin.
 *
 * @link       https://github.com/mnestorov/smarty-very-simple-license-manager
 * @since      1.0.1
 *
 * @package    Smarty_Very_Simple_License_Manager
 * @subpackage Smarty_Very_Simple_License_Manager/admin/tabs
 * @author     Smarty Studio | Martin Nestorov
 */
class Smarty_Vslm_Activity_Logging {

    /**
	 * Initializes the Activity Logging settings by registering the settings, sections, and fields.
	 *
	 * @since    1.0.1
	 */
    public function vslm_al_settings_init() {
        register_setting('smarty_vslm_options_activity_logging', 'smarty_vslm_settings_activity_logging');
		register_setting('smarty_vslm_options_activity_logging', 'smarty_vslm_settings_activity_log');

        add_settings_section(
			'smarty_vslm_section_activity_logging',								// ID of the section
			__('Activity & Logging', 'smarty-very-simple-license-manager'),    	// Title of the section  		
			array($this, 'vslm_section_tab_activity_logging_cb'),                // Callback function that fills the section with the desired content	
			'smarty_vslm_options_activity_logging'                   			// Page on which to add the section   		
		);

		add_settings_field(
            'smarty_vslm_system_info', 											// ID of the field
            __('System Info', 'smarty-very-simple-license-manager'), 			// Title of the field
            array($this, 'vslm_system_info_cb'), 								// Callback function to display the field
            'smarty_vslm_options_activity_logging', 								// Page on which to add the field
            'smarty_vslm_section_activity_logging' 								// Section to which this field belongs
        );

		add_settings_field(
			'smarty_vslm_activity_log',											// ID of the section
			__('Activity Log', 'smarty-very-simple-license-manager'),    		// Title of the section  		
			array($this, 'vslm_activity_log_cb'),                				// Callback function that fills the section with the desired content	
			'smarty_vslm_options_activity_logging',                   			// Page on which to add the section
			'smarty_vslm_section_activity_logging'								// Section to which this field belongs  		
		);
    }

    /**
	 * Callback function for the Activity & Logging main section.
	 *
	 * @since    1.0.1
	 */
	public static function vslm_section_tab_activity_logging_cb() {
		echo '<p>' . __('View and manage the activity logs for the plugin.', 'smarty-very-simple-license-manager') . '</p>';
	}

    /**
	 * Callback function for the Activity & Logging section field.
	 *
	 * @since    1.0.1
	 */
	public static function vslm_activity_log_cb() {
		$instance = new self('Smarty_Vslm_Admin', '1.0.1');
		$instance->vslm_display_activity_log();
	}

    /**
     * Callback function to display the system info.
     *
     * @since    1.0.1
     */
    public function vslm_system_info_cb() {
		$system_info = $this->get_system_info();
		echo '<ul>';
		foreach ($system_info as $key => $value) {
			echo '<li><strong>' . esc_html($key) . ':</strong> ' . wp_kses($value, array('span' => array('style' => array()))) . '</li>';
		}
		echo '</ul>';
	}

	/**
     * Get system information.
     *
     * @since    1.0.1
     * @return string System information.
     */
    private function get_system_info() {
		$system_info = array(
			'User Agent'          => esc_html($_SERVER['HTTP_USER_AGENT']),
			'Web Server'          => esc_html($_SERVER['SERVER_SOFTWARE']),
			'PHP Version'         => esc_html(PHP_VERSION),
			'PHP Max POST Size'   => esc_html(ini_get('post_max_size')),
			'PHP Max Upload Size' => esc_html(ini_get('upload_max_filesize')),
			'PHP Memory Limit'    => esc_html(ini_get('memory_limit')),
			'PHP DateTime Class'  => class_exists('DateTime') ? '<span style="color:#28a745;">Available</span>' : '<span style="color:#c51244;">Not Available</span>',
			'PHP Curl'            => function_exists('curl_version') ? '<span style="color:#28a745;">Available</span>' : '<span style="color:#c51244;">Not Available</span>',
		);
		
		return $system_info;
	}

	/**
	 * Display the activity log.
	 *
	 * @since    1.0.1
	 */
	public function vslm_display_activity_log() {
		// Retrieve log entries from the database or file
		$logs = get_option('smarty_vslm_activity_log', array());

		if (empty($logs)) {
			echo '<ul><li><span class="smarty-text-danger">' . esc_html__('Log empty', 'smarty-very-simple-license-manager') . '</span></li></ul>';
			echo '<button id="smarty-vslm-delete-logs-button" class="btn btn-danger disabled" disabled>' . esc_html__('Delete Logs', 'smarty-very-simple-license-manager') . '</button>';
		} else {
			echo '<ul>';
			foreach ($logs as $log) {
				echo '<li>' . esc_html($log) . '</li>';
			}
			echo '</ul>';
			echo '<button id="smarty-vslm-delete-logs-button" class="btn btn-danger">' . esc_html__('Delete Logs', 'smarty-very-simple-license-manager') . '</button>';
		}
	}

	/**
	 * Add an entry to the activity log.
	 *
	 * @since    1.0.1
	 * @param string $message The log message.
	 */
	public static function vslm_add_activity_log($message) {
        $logs = get_option('smarty_vslm_activity_log', array());
        
        // Get the current time in the specified format
        $time_format = 'Y-m-d H:i:s';  // PHP date format
        $current_time = current_time('mysql');  // Get current time in MySQL format
        
        // Get the timezone
        $timezone_string = get_option('timezone_string');
        if (!$timezone_string) {
            // If no timezone string is available, fall back to GMT offset
            $gmt_offset = get_option('gmt_offset');
            if ($gmt_offset) {
                $timezone_string = timezone_name_from_abbr('', $gmt_offset * 3600, 0);
            } else {
                $timezone_string = 'UTC';
            }
        }

        $timezone = new DateTimeZone($timezone_string);

        // Create DateTime object with current time and timezone
        $datetime = new DateTime($current_time, $timezone);
        $formatted_time = $datetime->format('Y-m-d H:i:s');  // Reformat time according to timezone
        $timezone_name = $datetime->format('T');  // Gets the timezone abbreviation, e.g., GMT
    
        // Combine time, timezone, and message
        $log_entry = sprintf("[%s %s] - %s", $formatted_time, $timezone_name, $message);
        $logs[] = $log_entry;
    
        // Save the updated logs back to the database
        update_option('smarty_vslm_activity_log', $logs);
    }

	/**
	 * Clear the activity log.
	 *
	 * @since    1.0.1
	 */
	public function vslm_clear_activity_log() {
		update_option('smarty_vslm_activity_log', array());
	}

	/**
	 * Handle the AJAX request to clear the logs.
	 *
	 * @since    1.0.1
	 */
	public function vslm_handle_ajax_clear_logs() {
		check_ajax_referer('smarty_vslm_license_nonce', 'nonce');

		if (!current_user_can('manage_options')) {
			wp_send_json_error('You do not have sufficient permissions to access this page.');
		}

		$this->vslm_clear_activity_log();
		wp_send_json_success(__('Logs cleared.', 'smarty-very-simple-license-manager'));
	}
}