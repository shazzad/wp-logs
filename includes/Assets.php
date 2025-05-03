<?php
/**
 * Register CSS/JS
 * 
 * @package Shazzad\WpLogs
 */

namespace Shazzad\WpLogs;

class Assets {

	public static function setup() {
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'register_admin_scripts' ], 5 );
	}

	public static function register_admin_scripts() {
		$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG || isset( $_GET['gform_debug'] ) ? '' : '.min';

		$min = '';

		wp_register_script( 'swpl-wp-debug-log', SWPL_URL . 'assets/js/wp-debug-log' . $min . '.js', [], SWPL_VERSION, true );
		wp_register_style( 'swpl-wp-debug-log', SWPL_URL . 'assets/css/wp-debug-log' . $min . '.css', [], SWPL_VERSION );
	}
}
