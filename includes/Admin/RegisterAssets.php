<?php
/**
 * Register CSS/JS
 * 
 * @package W4dev\Loggable
 */

namespace W4dev\Loggable\Admin;

class RegisterAssets
{
	public function __construct()
	{
		add_action( 'admin_enqueue_scripts', array( $this, 'register' ), 2 );
	}

	public function register()
	{
		$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG || isset( $_GET['gform_debug'] ) ? '' : '.min';

		// @TODO Not yet minifying.
		$min = '';

		wp_register_script( 'w4-loggable-admin-main', W4_LOGGABLE_URL . 'assets/js/admin'. $min .'.js', array(), W4_LOGGABLE_VERSION, true );
		wp_register_style( 'w4-loggable-admin-main', W4_LOGGABLE_URL . 'assets/css/admin'. $min .'.css', array(), W4_LOGGABLE_VERSION );
	}
}
