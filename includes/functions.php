<?php

/**
 * The plugin functions file.
 *
 * This is used to define general functions, shortcodes etc.
 * 
 * Important: Always use the `smarty_` prefix for function names.
 *
 * @link       https://github.com/mnestorov/smarty-very-simple-license-manager
 * @since      1.0.1
 *
 * @package    Smarty_Very_Simple_License_Manager
 * @subpackage Smarty_Very_Simple_License_Manager/includes
 * @author     Smarty Studio | Martin Nestorov
 */

if (!function_exists('smarty_vslm_get_license_count_by_status')) {
    /**
     * Helper function to get the count of licenses by status.
     *
     * @since       1.0.1
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

if (!function_exists('_vslm_write_logs')) {
	/**
     * Writes logs for the plugin.
     * 
     * @since      1.0.1
     * @param string $message Message to be logged.
     * @param mixed $data Additional data to log, optional.
     */
    function _vslm_write_logs($message, $data = null) {
        $log_entry = '[' . current_time('mysql') . '] ' . $message;
    
        if (!is_null($data)) {
            $log_entry .= ' - ' . print_r($data, true);
        }

        $logs_file = fopen(VSLM_BASE_DIR . DIRECTORY_SEPARATOR . "logs.txt", "a+");
        fwrite($logs_file, $log_entry . "\n");
        fclose($logs_file);
    }
}