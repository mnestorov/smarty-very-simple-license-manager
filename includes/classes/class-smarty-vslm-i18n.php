<?php

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://github.com/mnestorov/smarty-very-simple-license-manager
 * @since      1.0.1
 *
 * @package    Smarty_Very_Simple_License_Manager
 * @subpackage Smarty_Very_Simple_License_Manager/includes/classes
 * @author     Smarty Studio | Martin Nestorov
 */
class Smarty_Vslm_I18n {

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.1
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain(
			'smarty-very-simple-license-manager',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);
	}
}