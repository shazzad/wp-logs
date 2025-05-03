<?php
/**
 * Register CSS/JS for WordPress Debug Log viewing
 * 
 * @package Shazzad\WpLogs
 */

namespace Shazzad\WpLogs\Admin;

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
		add_action( 'wp_ajax_swpl_wp_debug_log', [ __CLASS__, 'wp_debug_log_ajax' ] );
		add_action( 'wp_ajax_swpl_delete_wp_debug_log', [ __CLASS__, 'delete_wp_debug_log_ajax' ] );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_scripts' ], 20 );
		add_action( 'admin_bar_menu', [ __CLASS__, 'admin_bar_menu' ], 1110 );
	}

	/**
	 * AJAX handler to retrieve WordPress debug log content.
	 *
	 * Validates user permissions and log file existence, then returns the content
	 * of the debug.log file for display in a modal.
	 *
	 * @since 1.0.0
	 * @return void Sends JSON response and exits.
	 */
	public static function wp_debug_log_ajax() {
		$modal = [
			'header' => __( 'WP Debug Log' )
		];

		if ( ! current_user_can( 'manage_options' ) ) {
			$modal['content'] = __( 'Unauthorized Request' );
			wp_send_json_error( [ 'modal' => $modal ] );
		}

		if ( ! file_exists( self::get_log_file() ) ) {
			$modal['content'] = __( '<code>debug.log</code> file does not exists.' );
			wp_send_json_error( [ 'modal' => $modal ] );
		}

		$content = file_get_contents( self::get_log_file() );
		if ( empty( $content ) ) {
			$modal['content'] = __( '<code>debug.log</code> file empty.' );
			wp_send_json_error( [ 'modal' => $modal ] );
		}

		$modal['content'] = '<pre>' . $content . '</pre>';
		$modal['footer']  = '<button type="button" class="button button-primary" id="swpl-wp-debug-log-delete-btn">Clear Logs</button>';

		wp_send_json_success( [ 'modal' => $modal ] );
	}

	/**
	 * AJAX handler to delete the WordPress debug log file.
	 *
	 * Validates user permissions and deletes the debug.log file if it exists.
	 *
	 * @since 1.0.0
	 * @return void Sends JSON response and exits.
	 */
	public static function delete_wp_debug_log_ajax() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				[
					'message' => __( 'Unauthorized Request' )
				]
			);
		}

		if ( ! file_exists( self::get_log_file() ) ) {
			wp_send_json_error(
				[
					'message' => __( 'No logs' )
				]
			);
		}

		unlink( self::get_log_file() );

		wp_send_json_success(
			[
				'message' => __( 'Deleted' )
			]
		);
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
				'title'  => __( 'WP Debug Log' ),
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
	protected static function get_log_file() {
		if ( is_string( WP_DEBUG_LOG ) ) {
			return WP_DEBUG_LOG;
		} else {
			return ABSPATH . 'wp-content/debug.log';
		}
	}
}
