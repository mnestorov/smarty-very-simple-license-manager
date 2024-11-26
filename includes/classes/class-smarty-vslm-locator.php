<?php

/**
 * The core plugin class.
 *
 * This is used to define attributes, functions, internationalization used across
 * both the admin-specific hooks, and public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @link       https://github.com/mnestorov/smarty-very-simple-license-manager
 * @since      1.0.1
 *
 * @package    Smarty_Very_Simple_License_Manager
 * @subpackage Smarty_Very_Simple_License_Manager/includes/classes
 * @author     Smarty Studio | Martin Nestorov
 */
class Smarty_Vslm_Locator {

	/**
	 * The loader that's responsible for maintaining and registering all hooks
	 * that power the plugin.
	 *
	 * @since    1.0.1
	 * @access   protected
	 * @var      Smarty_Vslm_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.1
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.1
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.1
	 */
	public function __construct() {
		if (defined('VSLM_VERSION')) {
			$this->version = VSLM_VERSION;
		} else {
			$this->version = '1.0.1';
		}

		$this->plugin_name = 'smarty-very-simple-license-manager';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Smarty_Vslm_Loader. Orchestrates the hooks of the plugin.
	 * - Smarty_Vslm_i18n. Defines internationalization functionality.
	 * - Smarty_Vslm_Admin. Defines all hooks for the admin area.
	 * - Smarty_Vslm_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.1
	 * @access   private
	 */
	private function load_dependencies() {
		/**
		 * The class responsible for orchestrating the actions and filters of the core plugin.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'classes/class-smarty-vslm-loader.php';

		/**
		 * The class responsible for defining internationalization functionality of the plugin.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'classes/class-smarty-vslm-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . '../admin/class-smarty-vslm-admin.php';

		/**
		 * The class responsible for Activity & Logging functionality in the admin area.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . '../admin/tabs/class-smarty-vslm-activity-logging.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing side of the site.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . '../public/class-smarty-vslm-public.php';

		// Run the loader
		$this->loader = new Smarty_Vslm_Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Smarty_Vslm_I18n class in order to set the domain and to
	 * register the hook with WordPress.
	 *
	 * @since    1.0.1
	 * @access   private
	 */
	private function set_locale() {
		$plugin_i18n = new Smarty_Vslm_i18n();

		$this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.1
	 * @access   private
	 */
	private function define_admin_hooks() {
		$plugin_admin = new Smarty_Vslm_Admin($this->get_plugin_name(), $this->get_version());

		$plugin_activity_logging = new Smarty_Vslm_Activity_Logging();

		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
		$this->loader->add_action('wp_dashboard_setup', $plugin_admin, 'vslm_add_dashboard_widget');
		$this->loader->add_action('init', $plugin_admin, 'vslm_register_license_post_type');
		$this->loader->add_filter('post_updated_messages', $plugin_admin, 'vslm_license_post_updated_messages');
		$this->loader->add_action('add_meta_boxes', $plugin_admin, 'vslm_add_license_meta_boxes');
		$this->loader->add_action('add_meta_boxes', $plugin_admin, 'vslm_add_json_response_meta_box');
		$this->loader->add_action('save_post', $plugin_admin, 'vslm_save_license_meta', 10, 2);
		$this->loader->add_filter('wp_insert_post_data', $plugin_admin, 'vslm_set_numeric_slug', 10, 2);
		$this->loader->add_action('init', $plugin_admin, 'vslm_register_product_taxonomy');
		$this->loader->add_action('admin_bar_menu', $plugin_admin, 'vslm_remove_admin_bar_view_posts', 999);
		$this->loader->add_filter('post_row_actions', $plugin_admin, 'vslm_remove_view_link', 10, 2);
		$this->loader->add_filter('post_row_actions', $plugin_admin, 'vslm_remove_quick_edit', 10, 2);
		$this->loader->add_filter('manage_vslm-licenses_posts_columns', $plugin_admin, 'vslm_add_license_columns');
		$this->loader->add_action('manage_vslm-licenses_posts_custom_column', $plugin_admin, 'vslm_fill_license_columns', 10, 2);
		$this->loader->add_filter('manage_edit-vslm-licenses_sortable_columns', $plugin_admin, 'vslm_sortable_license_columns');
		$this->loader->add_action('pre_get_posts', $plugin_admin, 'vslm_orderby_license_columns');
		$this->loader->add_action('admin_head', $plugin_admin, 'vslm_custom_admin_styles');
		$this->loader->add_action('admin_menu', $plugin_admin, 'vslm_add_settings_page');
		$this->loader->add_action('admin_init', $plugin_admin, 'vslm_settings_init');
		$this->loader->add_action('wp_ajax_vslm_generate_ck_key', $plugin_admin, 'vslm_generate_ck_key');
		$this->loader->add_action('wp_ajax_nopriv_vslm_generate_ck_key', $plugin_admin, 'vslm_generate_ck_key');
		$this->loader->add_action('wp_ajax_vslm_generate_cs_key', $plugin_admin, 'vslm_generate_cs_key');
		$this->loader->add_action('wp_ajax_nopriv_vslm_generate_cs_key', $plugin_admin, 'vslm_generate_cs_key');
		$this->loader->add_action('rest_api_init', $plugin_admin, 'vslm_register_license_status_endpoint');
		$this->loader->add_action('wp', $plugin_admin, 'vslm_schedule_cron_job');
		$this->loader->add_action('smarty_vslm_license_check', $plugin_admin, 'vslm_check_expired_licenses');
		$this->loader->add_action('admin_post_generate_license_pdf', $plugin_admin, 'generate_license_pdf');

		// Register hooks for Activity & Logging
		$this->loader->add_action('admin_init', $plugin_activity_logging, 'vslm_al_settings_init');
        $this->loader->add_action('wp_ajax_vslm_clear_logs', $plugin_activity_logging, 'vslm_handle_ajax_clear_logs');
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.1
	 * @access   private
	 */
	private function define_public_hooks() {
		$plugin_public = new Smarty_Vslm_Public($this->get_plugin_name(), $this->get_version());
		
		$this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
		$this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.1
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.1
	 * @return    Smarty_Vslm_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.1
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}
}