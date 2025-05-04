<?php
/**
 * Admin Environment
 * 
 * @package Shazzad\WpLogs
 */

namespace Shazzad\WpLogs\Admin;

/**
 * Main admin class responsible for initializing admin-related functionality.
 *
 * This class handles the setup of WordPress debug logs, admin pages,
 * and registers the plugin's action links.
 *
 * @since 1.0.0
 * @package Shazzad\WpLogs\Admin
 */
class Main {
	/**
	 * Set up the admin environment.
	 *
	 * Initializes various admin components and registers action links.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function setup() {
		AdminPage::setup();

		add_filter( 'plugin_action_links_' . SWPL_BASENAME, [ __CLASS__, 'plugin_action_links' ] );
	}

	/**
	 * Add plugin action links.
	 *
	 * Adds a "Logs" link to the plugin's action links on the plugins page.
	 *
	 * @since 1.0.0
	 * @param array $links An array of plugin action links.
	 * @return array Modified array of plugin action links.
	 */
	public static function plugin_action_links( $links ) {
		$links['logs'] = '<a href="' . admin_url( 'admin.php?page=shazzad-wp-logs' ) . '">' . __( 'Logs', 'swpl' ) . '</a>';
		return $links;
	}
}