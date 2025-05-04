<?php
/**
 * Register CSS/JS for WordPress Debug Log viewing
 * 
 * @package Shazzad\WpLogs
 */

namespace Shazzad\WpLogs;

use Shazzad\WpLogs\AdminBarMenu;

/**
 * WP Debug Log management class.
 *
 * Handles the display, viewing, and clearing of WordPress debug logs
 * via AJAX and admin bar integration.
 *
 * @since 1.0.0
 * @package Shazzad\WpLogs\Admin
 */
class WpDebugLog {
	/**
	 * Set up WordPress debug log functionality.
	 *
	 * Registers AJAX handlers, script enqueueing, and admin bar integration.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function setup() {
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_scripts' ], 20 );
		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_scripts' ], 20 );
		add_action( 'admin_bar_menu', [ __CLASS__, 'admin_bar_menu' ], 1110 );
	}

	/**
	 * Enqueue scripts and styles for debug log functionality.
	 *
	 * Only loads assets for users with manage_options capability.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function enqueue_scripts() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		wp_register_script(
			'swpl-wp-debug-log',
			SWPL_URL . 'assets/js/wp-debug-log.js',
			[],
			time(),
			true
		);

		wp_register_style(
			'swpl-wp-debug-log',
			SWPL_URL . 'assets/css/wp-debug-log.css',
			[],
			SWPL_VERSION
		);

		wp_localize_script(
			'swpl-wp-debug-log',
			'swplWpDebugLog',
			[
				'root'  => esc_url_raw( rest_url() ),
				'nonce' => wp_create_nonce( 'wp_rest' ),
			]
		);

		wp_enqueue_style( 'swpl-wp-debug-log' );
		wp_enqueue_script( 'swpl-wp-debug-log' );
	}

	/**
	 * Add debug log menu item to the admin bar.
	 *
	 * @since 1.0.0
	 * @param \WP_Admin_Bar $wp_admin_bar WordPress admin bar object.
	 * @return void
	 */
	public static function admin_bar_menu( $wp_admin_bar ) {
		$wp_admin_bar->add_node(
			[
				'id'     => AdminBarMenu::PARENT_ID . '-debug-log',
				'parent' => AdminBarMenu::PARENT_ID . '-secondary',
				'title'  => __( 'WP Debug Log', 'swpl' ),
				'href'   => site_url( '/wp-content/debug.log' )
			]
		);
	}

	/**
	 * Get the path to the WordPress debug log file.
	 *
	 * Checks if WP_DEBUG_LOG is defined as a string path, otherwise
	 * uses the default WordPress location.
	 *
	 * @since 1.0.0
	 * @return string Path to the debug log file.
	 */
	public static function get_log_file() {
		if ( is_string( WP_DEBUG_LOG ) ) {
			return WP_DEBUG_LOG;
		} else {
			return ABSPATH . 'wp-content/debug.log';
		}
	}
}
