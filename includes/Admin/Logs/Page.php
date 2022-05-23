<?php
/**
 * Logs Admin Page
 * @package Shazzad\WpLogs
 */

namespace Shazzad\WpLogs\Admin\Logs;

use Shazzad\WpLogs\Admin\PageInterface;
use Shazzad\WpLogs\Log\Data as LogData;
use Shazzad\WpLogs\Log\Api as LogApi;
use Shazzad\WpLogs\Utils;

class Page implements PageInterface
{
	function __construct()
	{
		add_action( 'admin_menu', array( $this, 'admin_menu' ), 100 );
		add_action( 'admin_menu', array( $this, 'add_submenus' ), 100 );
		add_filter( 'parent_file', array( $this, 'highlight_submenu' ) );
		add_filter( 'set-screen-option', [ $this, 'set_screen_option' ], 10, 3);
		add_filter( 'set_screen_option_swpl_logs_per_page', [ $this, 'set_screen_option' ], 10, 3);
	}

	public function admin_menu() {
		$access_cap = apply_filters( 'shazzad_wp_logs/page_access_cap/logs', 'manage_options' );
		$admin_page = add_menu_page(
			__( 'Logs', 'shazzad-wp-logs' ),
			__( 'Logs', 'shazzad-wp-logs' ),
			$access_cap,
			'shazzad-wp-logs',
			[$this, 'render_page'],
			'dashicons-warning'
		);

		add_action( "admin_print_styles-{$admin_page}", array( $this, 'print_scripts' ) );
		add_action( "load-{$admin_page}", array( $this, 'load_page' ) );
		add_action( "load-{$admin_page}", array( $this, 'handle_actions' ) );

		// Register menu for each of the item.
		$menu_items = Utils::get_menu_items();
		if ( ! empty( $menu_items ) ) {
			foreach ( $menu_items as $menu_slug => $menu_item ) {
				if ( ! empty( $menu_item['parent_slug'] ) ) {

					$admin_page = add_submenu_page(
						$menu_item['parent_slug'],
						$menu_item['page_title'],
						$menu_item['menu_title'],
						$menu_item['capability'],
						$menu_slug,
						[$this, 'render_page']
					);

					add_action( "admin_print_styles-{$admin_page}", array( $this, 'print_scripts' ) );
					add_action( "load-{$admin_page}", array( $this, 'load_page' ) );
					add_action( "load-{$admin_page}", array( $this, 'handle_actions' ) );
				}
			}
		}
	}

	public function set_screen_option( $status, $option, $value ) {
		if ( 'swpl_logs_per_page' == $option ) {
			return $value;
		}

		return $status;
	}

	public function handle_actions()
	{
		$req_action = isset( $_REQUEST['action'] ) ? $_REQUEST['action'] : '';

		if ( in_array( $req_action, [ 'delete', 'bulk_delete', 'delete_all' ] ) ) {
	  		$api = new LogApi();

			if ( 'delete' === $req_action ) {
				$handle = $api->handle( 'delete', $_REQUEST );
			} elseif ( 'bulk_delete' === $req_action ) {
				$handle = $api->handle( 'batch', array( 'delete' => $_REQUEST['ids'] ) );
			} elseif ( 'delete_all' === $req_action ) {
				$handle = $api->handle( 'delete_all', $_REQUEST );
			}

			$message = false;
			$error = false;

			if ( is_wp_error( $handle ) ) {
				$error = $handle->get_error_message();
			} elseif ( 'delete' === $req_action ) {
				$message = __( 'Log deleted', 'shazzad-wp-logs' );
			} elseif ( 'bulk_delete' === $req_action ) {
				$message = __( 'Logs deleted', 'shazzad-wp-logs' );
			} elseif ( 'delete_all' === $req_action ) {
				$message = $handle['message'];
			}

			wp_redirect(
				add_query_arg(
					array(
						'id' => false,
						'ids' => false,
						'action' => false,
						'message' => $message ? urlencode( $message ) : false,
						'error' => $error ? urldecode( $error ) : false
					)
				)
			);
			exit;
		}

		do_action( 'shazzad_wp_logs/admin_page/logs/handle_actions' );
	}

	public function load_page()
	{
		$req_action = isset( $_REQUEST['action'] ) ? $_REQUEST['action'] : '';

		if ( empty( $req_action ) || -1 == $req_action ) {
			global $w4LogsListTableLogs;
			$w4LogsListTableLogs = new ListTable();
			$w4LogsListTableLogs->prepare_items();
		}

		do_action( 'shazzad_wp_logs/admin_page/logs/load' );
	}

	public function render_notices()
	{
		do_action( 'shazzad_wp_logs/admin_page/logs/notices' );
		do_action( 'shazzad_wp_logs/admin_page/notices' );
	}

	public function render_page()
	{
		$req_action = isset( $_REQUEST['action'] ) ? $_REQUEST['action'] : '';

		$page_title = __( 'Logs' );
		$menu_item_key = false;

		if ( isset( $_REQUEST['menu_item'] ) && Utils::get_menu_item( $_REQUEST['menu_item'] ) ) {
			$menu_item = Utils::get_menu_item( $_REQUEST['menu_item'] );
			$page_title = $menu_item['page_title'];
			$menu_item_key = $_REQUEST['menu_item'];
		
		} elseif ( isset( $_REQUEST['page'] ) && Utils::get_menu_item( $_REQUEST['page'] ) ) {
			$menu_item = Utils::get_menu_item( $_REQUEST['page'] );
			$page_title = $menu_item['page_title'];
			$menu_item_key = $_REQUEST['page'];
		}

		?>

		<div class="wrap swpl-wrap">
			<?php

			if ( 'view' === $req_action && isset( $_REQUEST['id'] ) ) {
				$log = new LogData( intval( $_REQUEST['id'] ) );
				?>

				<h1 class="wp-heading-inline">
					<?php printf( __( 'View Log: # %d', 'shazzad-wp-logs' ), $log->get_id() ); ?>
				</h1>

				<a class="page-title-action" href="<?php echo remove_query_arg( array( 'id', 'action' ) ); ?>">
					<?php _e( 'Back to logs' ); ?>
				</a>

				<hr class="wp-header-end">

				<?php do_action( 'shazzad_wp_logs/admin_page/notices' ); ?>

				<div class="swpl-admin-content">
					<div class="swpl-preview">
						<div class="swpl-message">
							<strong class="swpl-level"><?php echo $log->get_level(); ?></strong>
							<?php echo apply_filters( 'swpl_format_message', $log->get_message(), $log->get_context() ); ?>
						</div>

						<?php
						if ( $log->get_context() ) {
							echo '<pre class="swpl-data">';
							print_r( $log->get_context() );
							echo '</pre>';
						}
						?>
					</div>
				</div>

			<?php } else if ( empty( $req_action ) || -1 == $req_action ) { ?>

				<h1 class="wp-heading-inline">
					<?php echo $page_title; ?>
				</h1>

				<a class="page-title-action" href="<?php 
					echo add_query_arg( 
						array( 
							'action' => 'delete_all',
							'menu_item' => $menu_item_key
						)
					);
					?>">
					<?php _e( 'Delete All' ); ?>
				</a>

				<hr class="wp-header-end">

				<?php do_action( 'shazzad_wp_logs/admin_page/notices' ); ?>

				<div class="swpl-admin-content">
					<?php include __DIR__ . '/views/list-table.php'; ?>
				</div>

			<?php } ?>

			<?php do_action( 'shazzad_wp_logs/admin_page/template_after/' ); ?>
		</div>

		<?php
	}

	public function add_submenus() {
		global $menu, $submenu;

		$menu_items = Utils::get_menu_items();

		// Remote items having parent_slug attribute, 
		// as they will be registered as page under their 
		// plugin menu.
		foreach ( $menu_items as $k => $v ) {
			if ( ! empty( $v['parent_slug'] ) ) {
				unset( $menu_items[ $k ] );
			}
		}

		if ( empty( $menu_items ) ) {
			return;
		}

		$access_cap = apply_filters( 'shazzad_wp_logs/page_access_cap/logs', 'manage_options' );

		if ( ! isset( $submenu['shazzad-wp-logs'] ) ) {
			$submenu['shazzad-wp-logs'] = array();
		}

		$submenu['shazzad-wp-logs'][] = array(
			__( 'All Logs' ),
			$access_cap,
			'admin.php?page=shazzad-wp-logs'
		);

		foreach ( $menu_items as $key => $menu_item ) {
			$submenu['shazzad-wp-logs'][] = array(
				$menu_item['menu_title'],
				$access_cap,
				'admin.php?page=shazzad-wp-logs&menu_item=' . $key
			);
		}
	}

	public function highlight_submenu( $parent_file ) {
		global $submenu_file;

		if ( 'shazzad-wp-logs' === $parent_file ) {
			$submenu_file = 'admin.php?page=shazzad-wp-logs';

			if ( isset( $_REQUEST['menu_item'] ) && Utils::get_menu_item( $_REQUEST['menu_item'] ) ) {
				$submenu_file = 'admin.php?page=shazzad-wp-logs&menu_item=' . wp_unslash( $_REQUEST['menu_item'] );
			}
		}

		return $parent_file;
	}

	public function print_scripts() {
		wp_enqueue_style( array( 'swpl-admin-logs' ) );
		wp_enqueue_script( array( 'swpl-admin-logs' ) );

		do_action( 'shazzad_wp_logs/admin_page/print_styles/logs' );
	}
}
