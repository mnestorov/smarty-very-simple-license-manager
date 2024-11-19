<?php

/**
 * The public functionality of the plugin.
 * 
 * Defines the plugin name, version, and two hooks for how to enqueue 
 * the public-facing stylesheet (CSS) and JavaScript code.
 * 
 * @link       https://github.com/mnestorov/smarty-very-simple-license-manager
 * @since      1.0.1
 *
 * @package    Smarty_Very_Simple_License_Manager
 * @subpackage Smarty_Very_Simple_License_Manager/public
 * @author     Smarty Studio | Martin Nestorov
 */
class Smarty_Vslm_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.1
	 * @access   private
	 * @var      string    $plugin_name     The ID of this plugin.
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
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.1
	 */
	public function enqueue_styles() {
		/**
		 * This function enqueues custom CSS for the WooCommerce checkout page.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Smarty_Form_Submissions_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Smarty_Form_Submissions_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/smarty-vslm-public.css', array(), $this->version, 'all');
    }

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.1
	 */
	public function enqueue_scripts() {
		/**
		 * This function enqueues custom JavaScript for the WooCommerce checkout page.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Smarty_Form_Submissions_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Smarty_Form_Submissions_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/smarty-vslm-public.js', array('jquery'), $this->version, true);
	}
}