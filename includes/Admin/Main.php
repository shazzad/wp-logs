<?php
/**
 * Admin Environment
 * 
 * @package W4dev\Loggable
 */

namespace W4dev\Loggable\Admin;

class Main
{
	function __construct()
	{
		$this->initialize();
		$this->register_hooks();
	}

	public function initialize()
	{
		new RegisterAssets();
		new Notices();
		new Logs\Page();
	}

	public function register_hooks()
	{
		add_filter( 'set-screen-option', array( $this, 'set_screen_option' ), 10, 3 );
		add_filter( 'plugin_action_links_' . W4_LOGGABLE_BASENAME , array( $this, 'plugin_action_links' ) );
	}

	public function set_screen_option( $status, $option, $value )
	{
		if ( ! empty( $option ) && 'w4_loggable' == substr( $option, 0, 11 ) ) {
			return $value;
		}

		return $status;
	}

	public function plugin_action_links( $links )
	{
		$links['logs'] = '<a href="'. admin_url( 'admin.php?page=w4-loggable' ) .'">' . __( 'Logs', 'w4-loggable' ). '</a>';
		return $links;
	}
}
