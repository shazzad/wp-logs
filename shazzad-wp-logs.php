<?php
/**
 * Plugin Name: Shazzad Wp Logs
 * Plugin URI: https://w4dev.com
 * Description: Store and view logs for debugging.
 * Version: 1.1.1
 * Requires at least: 4.4.0
 * Requires PHP: 5.5
 * Author: Shazzad Hossain Khan
 * Author URI: https://shazzad.me
 * Text Domain: shazzad-wp-logs
 * Domain Path: /languages
 * 
 * @package Shazzad\WpLogs
 */

// No direct access.
if (!defined('ABSPATH')) {
	return;
}

// Already loaded other way?
if (defined('SWPL_PLUGIN_FILE')) {
	return;
}

define('SWPL_VERSION', '1.1.1');

function swpl_missing_vendor_notice()
{
	?>
	<div class="notice notice-error is-dismissible">
		<p>
			<?php
			_e('Shazzad Wp Logs plugin is missing vendor folder. Please run <code>composer install</code> to import vendors.', 'shazzad-wp-logs');
			?>
		</p>
	</div>
	<?php
}

// When composer files are missing, stop further execution.
if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
	add_action('admin_notices', 'swpl_missing_vendor_notice');
	return;
}

define('SWPL_PLUGIN_FILE', __FILE__);

include_once __DIR__ . '/vendor/autoload.php';

/**
 * Function to get an instance of our Plugin
 */
function shazzad_wp_logs()
{
	return \Shazzad\WpLogs\Plugin::instance();

}

/**
 * Initialize asap
 */
shazzad_wp_logs();

/**
 * Install table to store log data
 */
function swpl_install()
{
	Shazzad\WpLogs\Installer::rename_tables();
	Shazzad\WpLogs\Installer::install_tables();
	Shazzad\WpLogs\Installer::update_tables();
}
register_activation_hook(SWPL_PLUGIN_FILE, 'swpl_install');


// Initialize updater if available.
if (class_exists('\Shazzad\GithubPlugin\Updater')) {
	new Shazzad\GithubPlugin\Updater(array(
		'file'         => __FILE__,
		'owner'        => 'shazzad',
		'repo'         => 'wp-logs',

		// Folloing only required for private repo.
		'private_repo' => false,
		'owner_name'   => 'Shazzad'
	));
}