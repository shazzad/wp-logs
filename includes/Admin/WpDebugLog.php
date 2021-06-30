<?php
/**
 * Register CSS/JS
 * 
 * @package Shazzad\WpLogs
 */

namespace Shazzad\WpLogs\Admin;

class WpDebugLog
{
	public function __construct()
	{
		add_action( 'wp_ajax_swpl_wp_debug_log', array( $this, 'wp_debug_log_ajax' ) );
		add_action( 'wp_ajax_swpl_delete_wp_debug_log', array( $this, 'delete_wp_debug_log_ajax' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ), 20 );
		add_action( 'admin_bar_menu', array( $this, 'admin_bar_menu' ), 1110 );
	}

	public function wp_debug_log_ajax()
	{
		$modal = array(
			'header' => __( 'WP Debug Log' )
		);

		if ( ! current_user_can( 'manage_options' ) ) {
			$modal['content'] = __( 'Unauthorized Request' );
			wp_send_json_error( array( 'modal' => $modal ) );
		}

		if ( ! file_exists( ABSPATH . '/wp-content/debug.log' ) ) {
			$modal['content'] = __( '<code>debug.log</code> file does not exists.' );
			wp_send_json_error( array( 'modal' => $modal ) );
		}

		$content = file_get_contents( ABSPATH . '/wp-content/debug.log' );
		if ( empty( $content ) ) {
			$modal['content'] = __( '<code>debug.log</code> file empty.' );
			wp_send_json_error( array( 'modal' => $modal ) );
		}

		$modal['content'] = '<pre>' . $content . '</pre>';
		$modal['footer'] = '<button type="button" class="button button-primary" id="swpl-wp-debug-log-delete-btn">Clear Logs</button>';

		wp_send_json_success( array( 'modal' => $modal ) );
	}

	public function delete_wp_debug_log_ajax()
	{
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Unauthorized Request' )
				)
			);
		}

		if ( ! file_exists( ABSPATH . '/wp-content/debug.log' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'No logs' )
				)
			);
		}

		unlink( ABSPATH . '/wp-content/debug.log' );

		wp_send_json_success(
			array(
				'message' => __( 'Deleted' )
			)
		);
	}

	public function enqueue_scripts()
	{
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		wp_enqueue_style( array( 'swpl-wp-debug-log' ) );
		wp_enqueue_script( array( 'swpl-wp-debug-log' ) );
	}

	public function admin_bar_menu( $wp_admin_bar )
	{
		// if ( file_exists( ABSPATH . '/wp-content/debug.log' ) ) {
			$wp_admin_bar->add_node(
				array(
					'id'     => AdminBarMenu::PARENT_ID . '-debug-log',
					'parent' => AdminBarMenu::PARENT_ID . '-secondary',
					'title'  => __( 'WP Debug Log' ),
					'href'   => site_url( '/wp-content/debug.log' )
				)
			);
		// }
	}
}
