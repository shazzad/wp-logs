<?php
/**
 * Register CSS/JS
 * 
 * @package Shazzad\WpLogs
 */

namespace Shazzad\WpLogs\Admin;

use Shazzad\WpLogs\Utils;

class AdminBarMenu
{
	const PARENT_ID = 'shazzad-wp-logs';

	public function __construct()
	{
		add_action( 'admin_bar_menu', array( $this, 'admin_bar_menu' ), 1100 );
	}

	public function admin_bar_menu( $wp_admin_bar )
	{
		// current user must have manage_options capability to see admin bar
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$wp_admin_bar->add_node(
			array(
				'id'    => self::PARENT_ID,
				// 'title' => __( 'WP Logs' ),
				'title' => '<span class="ab-icon dashicons-before dashicons-warning"></span><span class="screen-reader-text-no">' . __( 'Logs' ) . '</span>',
				'href'  => admin_url( 'admin.php?page=shazzad-wp-logs' ),
			)
		);

		$menu_items = Utils::get_menu_items();
		// Utils::d( $menu_items );

		if ( ! empty( $menu_items ) ) {
			$group_main = self::PARENT_ID . '-main';

			$wp_admin_bar->add_group(
					array(
						'id'   => $group_main,
						'parent' => self::PARENT_ID,
						'meta' => array(
							'class' => 'ab-top-main',
						),
					)
				);

			// Add all logs menu
			$wp_admin_bar->add_node(
				array(
					'id'     => self::PARENT_ID . '-all',
					'parent' => $group_main,
					'title'  => __( 'All Logs' ),
					'href'  => admin_url( 'admin.php?page=shazzad-wp-logs' )
				)
			);

			foreach ( $menu_items as $menu_slug => $menu_item ) {
				// Hide menu item if user doesn't have capability.
				if ( ! empty( $menu_item['capability'] ) && ! current_user_can( $menu_item['capability'] ) ) {
					continue;
				}

				$id = self::PARENT_ID . '-' . $menu_slug;

				if ( ! empty( $menu_item['bar_menu_title'] ) ) {
					$title = $menu_item['bar_menu_title'];
				} else {
					$title = ucwords( str_replace( '-', ' ', $menu_slug ) );
				}

				$href = admin_url( 'admin.php?page=' . $menu_slug );

				$wp_admin_bar->add_node(
					array(
						'id'     => $id,
						'parent' => $group_main,
						'title'  => $title,
						'href'   => $href,
					)
				);
			}

			$group_secondary = self::PARENT_ID . '-secondary';

			$wp_admin_bar->add_group(
				array(
					'id'   => $group_secondary,
					'parent' => self::PARENT_ID,
					'meta' => array(
						'class' => 'ab-sub-secondary',
					),
				)
			);

			if ( class_exists( 'WP_REST_API_Log' ) ) {
				$wp_admin_bar->add_node(
					array(
						'id'     => self::PARENT_ID . '-rest-api-log',
						'parent' => $group_secondary,
						'title'  => __( 'Rest Api Log' ),
						'href'   => admin_url( 'edit.php?post_type=wp-rest-api-log' ),
					)
				);
			}
		}
	}
}
