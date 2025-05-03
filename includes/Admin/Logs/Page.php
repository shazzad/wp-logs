<?php
/**
 * Logs Admin Page
 * 
 * @package Shazzad\WpLogs
 */

namespace Shazzad\WpLogs\Admin\Logs;

use Shazzad\WpLogs\Admin\PageInterface;

class Page implements PageInterface {
	public function __construct() {
		add_action( 'admin_menu', [ $this, 'admin_menu' ], 100 );
	}

	public function admin_menu() {
		$access_cap = apply_filters( 'shazzad_wp_logs/page_access_cap/logs', 'manage_options' );
		$admin_page = add_menu_page(
			__( 'Logs', 'shazzad-wp-logs' ),
			__( 'Logs', 'shazzad-wp-logs' ),
			$access_cap,
			'shazzad-wp-logs',
			[ $this, 'render_page' ],
			'dashicons-warning'
		);

		add_action( "admin_print_styles-{$admin_page}", [ $this, 'print_scripts' ] );
		add_action( "load-{$admin_page}", [ $this, 'load_page' ] );
		add_action( "load-{$admin_page}", [ $this, 'handle_actions' ] );
	}


	public function handle_actions() {
		do_action( 'shazzad_wp_logs/admin_page/logs/handle_actions' );
	}

	public function load_page() {
		do_action( 'shazzad_wp_logs/admin_page/logs/load' );
	}

	public function render_notices() {
		do_action( 'shazzad_wp_logs/admin_page/logs/notices' );
		do_action( 'shazzad_wp_logs/admin_page/notices' );
	}

	public function render_page() {
		?>
		<div class="wrap swpl-wrap">
			<div id="homerunner-react-app"></div>
			<?php do_action( 'shazzad_wp_logs/admin_page/template_after/' ); ?>
		</div>
		<?php
	}

	public function print_scripts() {
		do_action( 'shazzad_wp_logs/admin_page/print_styles/logs' );

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
