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

		$ver = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? time() : SWPL_VERSION;

		wp_register_script(
			'swpl-wp-debug-log',
			SWPL_URL . 'assets/js/wp-debug-log.js',
			[],
			$ver,
			true
		);

		wp_register_style(
			'swpl-wp-debug-log',
			SWPL_URL . 'assets/css/wp-debug-log.css',
			[],
			$ver
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
		/*
		 * The node only ever rendered for administrators because the parent
		 * group it hangs off is created by AdminBarMenu::admin_bar_menu() at
		 * priority 1100, which is gated. That is load-bearing coupling across
		 * two files and two priorities, so guard here as well rather than rely
		 * on it (#53).
		 */
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		/*
		 * Not site_url( '/wp-content/debug.log' ). Handing an administrator the
		 * raw file URL only works when the log happens to be readable over
		 * HTTP, and teaches them that it is - which is the opposite of what a
		 * debugging plugin should be teaching. The viewer in wp-debug-log.js
		 * intercepts this click and opens the log in a modal, reading it
		 * through the REST route behind manage_options; the href is where the
		 * click lands when that script has not loaded.
		 */
		$wp_admin_bar->add_node(
			[
				'id'     => AdminBarMenu::PARENT_ID . '-debug-log',
				'parent' => AdminBarMenu::PARENT_ID . '-secondary',
				'title'  => __( 'WP Debug Log', 'swpl' ),
				'href'   => admin_url( 'admin.php?page=' . AdminBarMenu::PARENT_ID ),
			]
		);
	}

	/**
	 * Get the path to the WordPress debug log file.
	 *
	 * Mirrors wp_debug_mode(): the strings 'true' and '1' mean the default
	 * location, any other string is a custom path, and the default location is
	 * WP_CONTENT_DIR/debug.log - not ABSPATH/wp-content, which is a different
	 * folder on sites that move wp-content.
	 *
	 * @since 1.0.0
	 * @return string Path to the debug log file.
	 */
	public static function get_log_file() {
		$default = WP_CONTENT_DIR . '/debug.log';

		if ( ! defined( 'WP_DEBUG_LOG' ) ) {
			return $default;
		}

		if ( in_array( strtolower( (string) WP_DEBUG_LOG ), [ 'true', '1' ], true ) ) {
			return $default;
		}

		if ( is_string( WP_DEBUG_LOG ) && '' !== WP_DEBUG_LOG ) {
			return WP_DEBUG_LOG;
		}

		return $default;
	}
}
