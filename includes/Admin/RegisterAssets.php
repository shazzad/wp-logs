<?php
/**
 * Register CSS/JS
 * 
 * @package Shazzad\WpLogs
 */

namespace Shazzad\WpLogs\Admin;

class RegisterAssets {

	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'register' ), 2 );
	}

	public function register() {
		$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG || isset( $_GET['gform_debug'] ) ? '' : '.min';

		$min = '';

		wp_register_script( 'swpl-admin', SWPL_URL . 'assets/js/admin' . $min . '.js', array( 'jquery' ), SWPL_VERSION, true );
		wp_register_style( 'swpl-admin', SWPL_URL . 'assets/css/admin' . $min . '.css', array(), SWPL_VERSION );

		wp_register_script( 'swpl-admin-logs', SWPL_URL . 'assets/js/admin-logs' . $min . '.js', array( 'swpl-admin' ), SWPL_VERSION, true );
		wp_register_style( 'swpl-admin-logs', SWPL_URL . 'assets/css/admin-logs' . $min . '.css', array( 'swpl-admin' ), SWPL_VERSION );

		wp_register_script( 'swpl-wp-debug-log', SWPL_URL . 'assets/js/wp-debug-log' . $min . '.js', [], SWPL_VERSION, true );
		wp_register_style( 'swpl-wp-debug-log', SWPL_URL . 'assets/css/wp-debug-log' . $min . '.css', [], SWPL_VERSION );
	}
}
