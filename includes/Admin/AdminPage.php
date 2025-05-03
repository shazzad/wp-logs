<?php
/**
 * Logs Admin Page
 * 
 * @package Shazzad\WpLogs
 */

namespace Shazzad\WpLogs\Admin;

/**
 * Admin Page class for WP Logs.
 *
 * Handles the creation and rendering of the admin page for the logs,
 * including menu registration and script enqueueing.
 *
 * @since 1.0.0
 * @package Shazzad\WpLogs\Admin
 */
class AdminPage {
	/**
	 * Set up the admin page.
	 *
	 * Registers the admin_menu action to add the logs page.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function setup() {
		add_action( 'admin_menu', [ __CLASS__, 'admin_menu' ], 100 );
	}

	/**
	 * Register the admin menu item for logs.
	 *
	 * Adds a top-level menu page for logs with the appropriate capability check.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function admin_menu() {
		$access_cap = apply_filters( 'shazzad_wp_logs/page_access_cap/logs', 'manage_options' );
		$admin_page = add_menu_page(
			__( 'Logs', 'swpl' ),
			__( 'Logs', 'swpl' ),
			$access_cap,
			'shazzad-wp-logs',
			[ __CLASS__, 'render_page' ],
			'dashicons-warning'
		);

		add_action( "admin_print_styles-{$admin_page}", [ __CLASS__, 'print_scripts' ] );
	}

	/**
	 * Render the logs admin page.
	 *
	 * Outputs the HTML container for the React-based logs application.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function render_page() {
		?>
		<div class="wrap swpl-wrap">
			<div id="homerunner-react-app"></div>
		</div>
		<?php
	}

	/**
	 * Enqueue scripts and styles for the admin page.
	 *
	 * Loads the necessary JavaScript and CSS files for the logs interface 
	 * and passes settings to the JavaScript application.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function print_scripts() {
		$asset_file = include SWPL_DIR . 'admin/build/index.asset.php';

		wp_enqueue_script(
			'swpl-admin-app',
			SWPL_URL . 'admin/build/index.js',
			$asset_file['dependencies'],
			$asset_file['version'],
			true
		);

		// Pass the `homelocalApiSettings` object
		wp_localize_script(
			'swpl-admin-app',
			'swplAdminAppSettings',
			array(
				'root'       => esc_url_raw( rest_url() ),
				'nonce'      => wp_create_nonce( 'wp_rest' ),
				'levels'     => swpl_get_levels(),
				'logSources' => swpl_get_sources(),
				'hostnames'  => swpl_get_request_hostnames(),
				'methods'    => swpl_get_request_methods(),

			)
		);

		// style.
		wp_enqueue_style(
			'swpl-admin-app',
			SWPL_URL . 'admin/build/index.css',
			[],
			time()
		);
	}
}
