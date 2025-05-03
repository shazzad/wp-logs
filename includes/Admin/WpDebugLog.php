<?php
/**
 * Register CSS/JS
 * 
 * @package Shazzad\WpLogs
 */

namespace Shazzad\WpLogs\Admin;

class WpDebugLog {
	public function __construct() {
		add_action( 'wp_ajax_swpl_wp_debug_log', [ $this, 'wp_debug_log_ajax' ] );
		add_action( 'wp_ajax_swpl_delete_wp_debug_log', [ $this, 'delete_wp_debug_log_ajax' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ], 20 );
		add_action( 'admin_bar_menu', [ $this, 'admin_bar_menu' ], 1110 );
	}

	public function wp_debug_log_ajax() {
		$modal = [
			'header' => __( 'WP Debug Log' )
		];

		if ( ! current_user_can( 'manage_options' ) ) {
			$modal['content'] = __( 'Unauthorized Request' );
			wp_send_json_error( [ 'modal' => $modal ] );
		}

		if ( ! file_exists( $this->get_log_file() ) ) {
			$modal['content'] = __( '<code>debug.log</code> file does not exists.' );
			wp_send_json_error( [ 'modal' => $modal ] );
		}

		$content = file_get_contents( $this->get_log_file() );
		if ( empty( $content ) ) {
			$modal['content'] = __( '<code>debug.log</code> file empty.' );
			wp_send_json_error( [ 'modal' => $modal ] );
		}

		$modal['content'] = '<pre>' . $content . '</pre>';
		$modal['footer']  = '<button type="button" class="button button-primary" id="swpl-wp-debug-log-delete-btn">Clear Logs</button>';

		wp_send_json_success( [ 'modal' => $modal ] );
	}

	public function delete_wp_debug_log_ajax() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				[
					'message' => __( 'Unauthorized Request' )
				]
			);
		}

		if ( ! file_exists( $this->get_log_file() ) ) {
			wp_send_json_error(
				[
					'message' => __( 'No logs' )
				]
			);
		}

		unlink( $this->get_log_file() );

		wp_send_json_success(
			[
				'message' => __( 'Deleted' )
			]
		);
	}

	public function enqueue_scripts() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		wp_enqueue_style( 'swpl-wp-debug-log' );
		wp_enqueue_script( 'swpl-wp-debug-log' );
	}

	public function admin_bar_menu( $wp_admin_bar ) {
		$wp_admin_bar->add_node(
			[
				'id'     => AdminBarMenu::PARENT_ID . '-debug-log',
				'parent' => AdminBarMenu::PARENT_ID . '-secondary',
				'title'  => __( 'WP Debug Log' ),
				'href'   => site_url( '/wp-content/debug.log' )
			]
		);
	}

	protected function get_log_file() {
		if ( is_string( WP_DEBUG_LOG ) ) {
			return WP_DEBUG_LOG;
		} else {
			return ABSPATH . 'wp-content/debug.log';
		}
	}
}
