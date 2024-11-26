<?php

/**
 * The plugin bootstrap file.
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link                    https://github.com/mnestorov/smarty-very-simple-license-manager
 * @since                   1.0.1
 * @package                 Smarty_Very_Simple_License_Manager
 *
 * @wordpress-plugin
 * Plugin Name:             SM - Very SImple License Manager
 * Plugin URI:              https://github.com/mnestorov/smarty-very-simple-license-manager
 * Description:             A plugin to manage licenses with custom post types, status management, and API keys.
 * Version:                 1.0.2
 * Author:                  Smarty Studio | Martin Nestorov
 * Author URI:              https://github.com/mnestorov
 * License:                 GPL-2.0+
 * License URI:             http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:             smarty-very-simple-license-manager
 * Domain Path:             /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
	die;
}

// Check if VSLM_VERSION is not already defined
if (!defined('VSLM_VERSION')) {
	/**
	 * Current plugin version.
	 * For the versioning of the plugin is used SemVer - https://semver.org
	 */
	define('VSLM_VERSION', '1.0.2');
}

// Check if VSLM_BASE_DIR is not already defined
if (!defined('VSLM_BASE_DIR')) {
	/**
	 * This constant is used as a base path for including other files or referencing directories within the plugin.
	 */
    define('VSLM_BASE_DIR', dirname(__FILE__));
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/classes/class-smarty-vslm-activator.php
 * 
 * @since    1.0.1
 */
function activate_vslm() {
	require_once plugin_dir_path(__FILE__) . 'includes/classes/class-smarty-vslm-activator.php';
	Smarty_Vslm_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/classes/class-smarty-vslm-deactivator.php
 * 
 * @since    1.0.1
 */
function deactivate_vslm() {
	require_once plugin_dir_path(__FILE__) . 'includes/classes/class-smarty-vslm-deactivator.php';
	Smarty_Vslm_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_vslm');
register_deactivation_hook(__FILE__, 'deactivate_vslm');

/**
 * Include Composer autoloader.
 */
require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/classes/class-smarty-vslm-locator.php';

/**
 * The plugin functions file that is used to define general functions, shortcodes etc.
 */
require plugin_dir_path(__FILE__) . 'includes/functions.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.1
 */
function run_vslm() {
	$plugin = new Smarty_Vslm_Locator();
	$plugin->run();
}

run_vslm();