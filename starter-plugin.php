<?php
/**
 * Plugin Name: Starter Plugin
 * Plugin URI:  https://mokhtarbensaid.com
 * Description: A starter template for building WordPress plugins with best practices.
 * Version:     1.2.0
 * Author:      Mokhtar Bensaid
 * Author URI:  https://mokhtarbensaid.com
 * License:     GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: starter-plugin
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

// If accessed directly, deny access.
defined('ABSPATH') || exit;

// Definition of plugin constants.
define('STARTER_PLUGIN_VERSION', '1.2.0');
define('STARTER_PLUGIN_FILE', __FILE__);
define('STARTER_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('STARTER_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('STARTER_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include essential files.
require_once STARTER_PLUGIN_PATH . 'includes/class-activator.php';
require_once STARTER_PLUGIN_PATH . 'includes/class-deactivator.php';
require_once STARTER_PLUGIN_PATH . 'includes/class-main.php';

// Code that works when activation.
register_activation_hook(__FILE__, ['Starter_Plugin_Activator', 'activate']);

// Code that works when deactivation.
register_deactivation_hook(__FILE__, ['Starter_Plugin_Deactivator', 'deactivate']);

// Run the plugin.
Starter_Plugin_Main::instance();