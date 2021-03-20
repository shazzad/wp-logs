<?php
/**
 * Logs Admin Page
 * @package W4dev\Loggable
 */

namespace W4dev\Loggable\Admin\Logs;

use W4dev\Loggable\Admin\PageInterface;
use W4dev\Loggable\Log\Data as LogData;
use W4dev\Loggable\Log\Api as LogApi;
use W4dev\Loggable\Utils;

class Page implements PageInterface
{
	function __construct()
	{
		add_action( 'admin_menu', array( $this, 'admin_menu' ), 15 );
		add_action( 'admin_menu', array( $this, 'add_submenus' ), 100 );
		add_filter( 'parent_file', array( $this, 'highlight_submenu' ) );
		add_filter( 'set-screen-option', [ $this, 'set_screen_option' ], 10, 3);
		add_filter( 'set_screen_option_w4_loggable_logs_per_page', [ $this, 'set_screen_option' ], 10, 3);
	}

	public function set_screen_option( $status, $option, $value ) {
		if ( 'w4_loggable_logs_per_page' == $option ) {
			return $value;
		}

		return $status;
	}

	public function handle_actions()
	{
		$req_action = isset( $_REQUEST['action'] ) ? $_REQUEST['action'] : '';

		if ( in_array( $req_action, [ 'delete', 'bulk_delete' ] ) ) {
	  		$api = new LogApi();

			if ( 'delete' === $req_action ) {
				$handle = $api->handle( 'delete', $_REQUEST );
				$message = __( 'Log deleted', 'w4-loggable' );
			} elseif ( 'bulk_delete' === $req_action ) {
				$handle = $api->handle( 'batch', array( 'delete' => $_REQUEST['ids'] ) );
				$message = __( 'Logs deleted', 'w4-loggable' );
			}

			wp_redirect( add_query_arg( array(
				'id' => false,
				'ids' => false,
				'action' => false,
				'message' => urlencode( $message )
			) ) );
			exit;
		}

		do_action( 'w4_loggable/admin_page/logs/handle_actions' );
	}

	public function load_page()
	{
		$req_action = isset( $_REQUEST['action'] ) ? $_REQUEST['action'] : '';

		if ( empty( $req_action ) || -1 == $req_action ) {
			global $w4LogsListTableLogs;
			$w4LogsListTableLogs = new ListTable();
			$w4LogsListTableLogs->prepare_items();
		}

		do_action( 'w4_loggable/admin_page/logs/load' );
	}

	public function render_notices()
	{
		do_action( 'w4_loggable/admin_page/logs/notices' );
		do_action( 'w4_loggable/admin_page/notices' );
	}

	public function render_page()
	{
		$req_action = isset( $_REQUEST['action'] ) ? $_REQUEST['action'] : '';

		if ( isset( $_REQUEST['menu_item'] ) ) {
			$menu_item = Utils::get_menu_item( $_REQUEST['menu_item'] );
		}

		$page_title = __( 'Logs' );
		if ( $menu_item ) {
			$page_title = $menu_item['page_title'];
		}

		?>

		<div class="wrap w4_loggable_wrap">
			<?php

			if ( in_array( $req_action, array( 'view' ) ) && isset( $_REQUEST['id'] ) ) {

				$log = new LogData( intval( $_REQUEST['id'] ) );

				?>

				<h1 class="wp-heading-inline">
					<?php printf( __( 'View Log: # %d', 'w4-loggable' ), $log->get_id() ); ?>
				</h1>

				<a class="page-title-action" href="<?php echo remove_query_arg( array( 'id', 'action' ) ); ?>">Back to logs</a>

				<hr class="wp-header-end">

				<?php do_action( 'w4_loggable/admin_page/notices' ); ?>

				<div class="w4-loggable-admin-content">
					<div class="box">
						<?php
						echo apply_filters( 'w4_loggable_format_message', $log->get_message(), $log->get_context() );
						if ( $log->get_context() ) {
							echo '<pre>';
							print_r( $log->get_context() );
							echo '</pre>';
						}
						?>
					</div>
				</div>

			<?php } else if ( empty( $req_action ) || -1 == $req_action ) { ?>

				<h1><?php echo $page_title; ?></h1>

				<?php do_action( 'w4_loggable/admin_page/notices' ); ?>

				<div class="w4-loggable-admin-content">
					<?php include __DIR__ . '/views/list-table.php'; ?>
				</div>

			<?php } ?>

			<?php do_action( 'w4_loggable/admin_page/template_after/' ); ?>
		</div>

		<?php
	}

	public function admin_menu() {
		$access_cap = apply_filters( 'w4_loggable/page_access_cap/logs', 'manage_options' );
		$admin_page = add_menu_page(
			__( 'Logs', 'w4-loggable' ),
			__( 'Logs', 'w4-loggable' ),
			$access_cap,
			'w4-loggable',
			[$this, 'render_page'],
			'dashicons-info-outline'
		 );

		add_action( "admin_print_styles-{$admin_page}", array( $this, 'print_scripts' ) );
		add_action( "load-{$admin_page}", array( $this, 'load_page' ) );
		add_action( "load-{$admin_page}", array( $this, 'handle_actions' ) );
	}

	public function add_submenus() {
		global $menu, $submenu;

		$menu_items = Utils::get_menu_items();
		if ( empty( $menu_items ) ) {
			return;
		}

		$access_cap = apply_filters( 'w4_loggable/page_access_cap/logs', 'manage_options' );

		if ( ! isset( $submenu['w4-loggable'] ) ) {
			$submenu['w4-loggable'] = array();
		}

		$submenu['w4-loggable'][] = array(
			__( 'All Logs' ),
			$access_cap,
			'admin.php?page=w4-loggable'
		);

		foreach ( $menu_items as $key => $menu_item ) {
			$submenu['w4-loggable'][] = array(
				$menu_item['menu_title'],
				$access_cap,
				'admin.php?page=w4-loggable&menu_item=' . $key
			);
		}
	}

	public function highlight_submenu( $parent_file ) {
		global $submenu_file;

		if ( 'w4-loggable' === $parent_file ) {
			$submenu_file = 'admin.php?page=w4-loggable';

			if ( isset( $_REQUEST['menu_item'] ) && Utils::get_menu_item( $_REQUEST['menu_item'] ) ) {
				$submenu_file = 'admin.php?page=w4-loggable&menu_item=' . wp_unslash( $_REQUEST['menu_item'] );
			}
		}

		return $parent_file;
	}

	public function print_scripts() {
		wp_enqueue_style( array( 'w4-loggable-admin-main' ) );
		wp_enqueue_script( array( 'w4-loggable-admin-main' ) );

		do_action( 'w4_loggable/admin_page/print_styles/logs' );
	}
}
