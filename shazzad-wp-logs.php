<?php
/**
 * Plugin Name: Shazzad Wp Logs
 * Plugin URI: https://w4dev.com
 * Description: Store and view logs for debugging.
 * Version: 2.0.1
 * Requires at least: 4.4.0
 * Requires PHP: 7.4
 * Author: Shazzad Hossain Khan
 * Author URI: https://shazzad.me
 * Text Domain: swpl
 * Domain Path: /languages
 * 
 * @package Shazzad\WpLogs
 */

// No direct access.
if ( ! defined( 'ABSPATH' ) ) {
	return;
}

// Already loaded other way?
if ( defined( 'SWPL_PLUGIN_FILE' ) ) {
	return;
}

define( 'SWPL_VERSION', '2.0.1' );
define( 'SWPL_PLUGIN_FILE', __FILE__ );
define( 'SWPL_DIR', plugin_dir_path( SWPL_PLUGIN_FILE ) );
define( 'SWPL_URL', plugin_dir_url( SWPL_PLUGIN_FILE ) );
define( 'SWPL_BASENAME', plugin_basename( SWPL_PLUGIN_FILE ) );

function swpl_missing_vendor_notice() {
	?>
	<div class="notice notice-error is-dismissible">
		<p>
			<?php
			_e( 'Shazzad Wp Logs plugin is missing vendor folder. Please run <code>composer install</code> to import vendors.', 'swpl' );
			?>
		</p>
	</div>
	<?php
}

// When composer files are missing, stop further execution.
if ( ! file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	add_action( 'admin_notices', 'swpl_missing_vendor_notice' );
	return;
}

/**
 * Function to get an instance of our Plugin
 */
function shazzad_wp_logs() {
	require_once __DIR__ . '/includes/Plugin.php';

	return \Shazzad\WpLogs\Plugin::instance();

}

/**
 * Initialize asap.
 */
shazzad_wp_logs();

/**
 * Install table to store log data
 */
function swpl_install() {
	require_once __DIR__ . '/includes/Installer.php';

	Shazzad\WpLogs\Installer::activate();
}
register_activation_hook( SWPL_PLUGIN_FILE, 'swpl_install' );

// Dev cli command.
if ( defined( 'WP_CLI' )
	&& WP_CLI
	&& file_exists( __DIR__ . '/commands/GenerateLogsCommand.php' ) ) {

	require_once __DIR__ . '/vendor/autoload.php';
	require_once __DIR__ . '/commands/GenerateLogsCommand.php';
}

// Initialize updater if available.
if ( class_exists( '\Shazzad\GithubPlugin\Updater' ) ) {
	new Shazzad\GithubPlugin\Updater( [
		'file'         => __FILE__,
		'owner'        => 'shazzad',
		'repo'         => 'wp-logs',

		// Following only required for private repo.
		'private_repo' => false,
		'owner_name'   => 'Shazzad'
	] );
}