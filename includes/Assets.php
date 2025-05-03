<?php
/**
 * Assets Class
 *
 * Handles registration and enqueuing of CSS and JavaScript assets for the plugin.
 *
 * @package    Shazzad\WpLogs
 * @author     Shazzad Hossain
 * @since      1.0.0
 * @version    1.0.0
 */

namespace Shazzad\WpLogs;

/**
 * Assets Class
 *
 * Manages script and style registration for the WordPress Logs plugin.
 */
class Assets {

	/**
	 * Initialize assets
	 *
	 * Hooks into WordPress to register scripts and styles.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function setup() {
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'register_admin_scripts' ], 5 );
	}

	/**
	 * Register admin scripts and styles
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function register_admin_scripts() {
		wp_register_script( 'swpl-wp-debug-log', SWPL_URL . 'assets/js/wp-debug-log.js', [], SWPL_VERSION, true );
		wp_register_style( 'swpl-wp-debug-log', SWPL_URL . 'assets/css/wp-debug-log.css', [], SWPL_VERSION );
	}
}
