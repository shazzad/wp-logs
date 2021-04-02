<?php
/**
 * Plugin Name: W4 Loggable
 * Plugin URI: https://w4dev.com
 * Description: Store and view logs for debugging.
 * Version: 1.0.4
 * Requires at least: 4.4.0
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

// If autoloader is missing, stop silently
if ( ! file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	return;
}

define( 'W4_LOGS_PLUGIN_FILE', __FILE__ );

include_once __DIR__ . '/vendor/autoload.php';


/**
 * Get a instance of plugin
 */
function w4_loggable() {
	return \W4dev\Loggable\Plugin::instance();
}

/**
 * Initialize asap
 */
w4_loggable();


/**
 * Install table to store log data
 */
function w4_loggable_install() {
	\W4dev\Loggable\Installer::install_tables();
	\W4dev\Loggable\Installer::update_tables();
}
register_activation_hook( W4_LOGS_PLUGIN_FILE, 'w4_loggable_install', 10 );


// Initialize updater if available.
if ( class_exists( '\Shazzad\GithubPlugin\Updater' ) ) {
	new \Shazzad\GithubPlugin\Updater( array(
		'file'         => __FILE__,
		'owner'        => 'shazzad',
		'repo'		   => 'w4-loggable',
	
		// Folloing only required for private repo
		'private_repo' => false,
		'owner_name'   => 'Shazzad'
	) );
}