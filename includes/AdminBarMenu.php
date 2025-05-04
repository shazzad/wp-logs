<?php
/**
 * Admin Bar Menu
 * 
 * @package Shazzad\WpLogs
 */

namespace Shazzad\WpLogs;

/**
 * Class for managing the admin bar menu items for the logs plugin.
 *
 * Responsible for adding logs-related items to WordPress admin bar menu,
 * providing quick access to various log sections.
 *
 * @since 1.0.0
 * @package Shazzad\WpLogs
 */
class AdminBarMenu {

	/**
	 * Parent ID for all admin bar menu items.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public const PARENT_ID = 'shazzad-wp-logs';

	/**
	 * Set up the admin bar menu.
	 *
	 * Registers the admin_bar_menu action to add logs items.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function setup() {
		add_action( 'admin_bar_menu', [ __CLASS__, 'admin_bar_menu' ], 1100 );
	}

	/**
	 * Add items to the WordPress admin bar menu.
	 *
	 * Creates a top-level menu item for logs with submenu items for different log types.
	 * Only users with manage_options capability can see these menu items.
	 *
	 * @since 1.0.0
	 * @param \WP_Admin_Bar $wp_admin_bar WordPress admin bar object.
	 * @return void
	 */
	public static function admin_bar_menu( $wp_admin_bar ) {
		// Current user must have manage_options capability to see admin bar
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$wp_admin_bar->add_node( [
			'id'    => self::PARENT_ID,
			'title' => '<span class="ab-icon dashicons-before dashicons-warning"></span><span class="screen-reader-text-no">' . __( 'Logs', 'swpl' ) . '</span>',
			'href'  => admin_url( 'admin.php?page=shazzad-wp-logs' ) . '#logs',
			'meta'  => [
				'title' => __( 'Logs', 'swpl' ),
				'class' => 'ab-sub-secondary',
			],
		] );

		$group_secondary = self::PARENT_ID . '-secondary';

		$wp_admin_bar->add_group( [
			'id'     => $group_secondary,
			'parent' => self::PARENT_ID,
			'meta'   => [
				'class' => 'ab-sub-secondary',
			],
		] );

		if ( class_exists( 'WP_REST_API_Log' ) ) {
			$wp_admin_bar->add_node( [
				'id'     => self::PARENT_ID . '-rest-api-log',
				'parent' => $group_secondary,
				'title'  => __( 'REST API Log', 'swpl' ),
				'href'   => admin_url( 'edit.php?post_type=wp-rest-api-log' ),
			] );
		}
	}
}
