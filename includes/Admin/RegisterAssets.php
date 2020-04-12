<?php
namespace W4dev\Loggable\Admin;

/**
 * Register css & js
 * @package WordPress
 * @subpackage SERVED Admin
 * @author Shazzad Hossain Khan
 * @url https://shazzad.me
**/


class RegisterAssets
{
	public function __construct()
	{
		add_action( 'admin_enqueue_scripts', array( $this, 'register' ), 2 );
	}

	public function register()
	{
		$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG || isset(  $_GET['gform_debug']  ) ? '' : '.min';

		wp_register_script( 'W4_loggable-admin-main', W4_Loggable_URL . 'admin/js/admin'. $min .'.js', array(), W4_LOGGABLE_VERSION, true );
		wp_register_style( 'W4_loggable-admin-main', W4_Loggable_URL . 'admin/css/admin'. $min .'.css', array(), W4_LOGGABLE_VERSION );
	}
}
