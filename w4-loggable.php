<?php
/**
 * Plugin Name: W4 Loggable
 * Plugin URI: https://w4dev.com
 * Description: A plugin to store and visualize logs for debugging.
 * Version: 1.0.0
 * Requires at least: 4.4.0
 * Tested up to: 5.3.2
 * Requires PHP: 5.5
 * Author: Shazzad Hossain Khan
 * Author URI: https://shazzad.me
 * Text Domain: w4-loggable
 * Domain Path: /languages
 */


/* Define current file as plugin file */
if (! defined('W4_LOGS_PLUGIN_FILE')) {
	define('W4_LOGS_PLUGIN_FILE', __FILE__);
}


include_once (dirname(__FILE__) . '/vendor/autoload.php');


/* Plugin instance caller */
function w4_loggable() {
	return \W4dev\Loggable\Plugin::instance();
}


/* Initialize */
add_action('plugins_loaded', 'w4_loggable_init');
function w4_loggable_init() {
	w4_loggable();
}


/* Install additional db tables */
register_activation_hook(W4_LOGS_PLUGIN_FILE, 'w4_loggable_install', 10);
function w4_loggable_install() {
	include_once(plugin_dir_path(W4_LOGS_PLUGIN_FILE) . 'includes/class-database.php');
	include_once(plugin_dir_path(W4_LOGS_PLUGIN_FILE) . 'includes/class-installer.php');
	\W4dev\Loggable\Installer::install_tables();
	\W4dev\Loggable\Installer::update_tables();
}
