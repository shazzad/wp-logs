<?php
/**
 * Admin Environment
 * 
 * @package Shazzad\WpLogs
 */

namespace Shazzad\WpLogs\Admin;

class Main
{
	function __construct()
	{
		$this->initialize();
		$this->register_hooks();
	}

	public function initialize()
	{
		new AjaxHandler();
		new RegisterAssets();
		new Notices();
		new AdminBarMenu();
		new WpDebugLog();
		new Logs\Page();
	}

	public function register_hooks()
	{
		add_filter( 'set-screen-option', array( $this, 'set_screen_option' ), 10, 3 );
		add_filter( 'plugin_action_links_' . SWPL_BASENAME , array( $this, 'plugin_action_links' ) );
	}

	public function set_screen_option( $status, $option, $value )
	{
		if ( ! empty( $option ) && 'shazzad_wp_logs' == substr( $option, 0, 11 ) ) {
			return $value;
		}

		return $status;
	}

	public function plugin_action_links( $links )
	{
		$links['logs'] = '<a href="'. admin_url( 'admin.php?page=shazzad-wp-logs' ) .'">' . __( 'Logs', 'shazzad-wp-logs' ). '</a>';
		return $links;
	}
}
