<?php
/**
 * Plugin Name: W4 Loggable
 * Plugin URI: https://w4dev.com
 * Description: Store and view logs for debugging.
 * Version: 1.0.2
 * Requires at least: 4.4.0
 * Tested up to: 5.3.2
 * Requires PHP: 5.5
 * Author: Shazzad Hossain Khan
 * Author URI: https://shazzad.me
 * Text Domain: w4-loggable
 * Domain Path: /languages
 * 
 * @package W4dev\Loggable
 */

// No direct access.
if ( ! defined( 'ABSPATH' ) ) {
	return;
}

// Already loaded other way?
if ( defined( 'W4_LOGS_PLUGIN_FILE' ) ) {
	return;
}

if ( ! defined( 'W4_LOGS_PLUGIN_FILE' ) ) {
	define( 'W4_LOGS_PLUGIN_FILE', __FILE__ );
}

include_once __DIR__ . '/vendor/autoload.php';

/**
 * Get a instance of plugin
 */
function w4_loggable() {
	return \W4dev\Loggable\Plugin::instance();
}

/**
 * Initialize plugin on plugins loaded hook
 */
function w4_loggable_init() {
	w4_loggable();
}
add_action( 'plugins_loaded', 'w4_loggable_init' );

/**
 * Install table to store log
 */
function w4_loggable_install() {
	\W4dev\Loggable\Installer::install_tables();
	\W4dev\Loggable\Installer::update_tables();
}
register_activation_hook(W4_LOGS_PLUGIN_FILE, 'w4_loggable_install', 10);
