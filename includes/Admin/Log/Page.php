<?php
namespace W4dev\Loggable\Admin\Log;

use W4dev\Loggable\Admin\PageInterface;
use W4dev\Loggable\Log\Data as LogData;
use W4dev\Loggable\Log\Api as LogApi;

/**
 * Logs Admin Page
 * @package WordPress
 * @subpackage Cricket Engine
 * @author Shazzad Hossain Khan
 * @url https://shazzad.me
**/

class Page implements PageInterface
{
	function __construct()
	{
		add_action( 'admin_menu', array( $this, 'admin_menu' ), 15 );
	}

	public function handle_actions()
	{
		$req_action = isset( $_REQUEST['action'] ) ? $_REQUEST['action'] : '';

		if ( in_array( $req_action, [ 'delete', 'bulk_delete' ] ) ) {
	  		$api = new LogApi();

			if ( 'delete' === $req_action ) {
				$handle = $api->handle( 'delete', $_REQUEST );
				$message = 'Log deleted';
			} elseif ( 'bulk_delete' === $req_action ) {
				$handle = $api->handle( 'batch', array( 'delete' => $_REQUEST['ids'] ) );
				$message = 'Logs deleted';
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
		$req_action = isset( $_GET['action'] ) ? $_GET['action'] : '';
		/*
		$log = new W4_Loggable_Log();
		$log->set_message( 'Hello {{user}}' );
		$log->set_context( ['user' => 'Shazzad'] );
		$log->save();
		W4_Loggable_utils::p( $log );
		*/
		?><div class="wrap w4_loggable_wrap"><?php

		if ( in_array( $req_action, array( 'view' ) ) && isset( $_REQUEST['id'] ) ) {
			$log = new LogData( (int) $_REQUEST['id'] );
			?>
			<h1 class="wp-heading-inline"><?php _e( 'View Log' ); ?> : # <strong><?php echo $log->get_id(); ?></strong></h1>
			<a class="page-title-action" href="<?php echo admin_url( 'tools.php?page=w4-loggable-logs'); ?>">Back to logs</a>
			<hr class="wp-header-end">

			<?php do_action( 'w4_loggable/admin_page/notices' ); ?>

			<div class="w4_loggable_admin_content">
				<div class="box"><?php
					echo apply_filters( 'w4_loggable_format_message', $log->get_message(), $log->get_context() );
					if ( $log->get_context() ) {
						echo '<pre>';
						print_r( $log->get_context() );
						echo '</pre>';
					}
				?></div>
			</div><?php
		} else if ( empty( $req_action ) || -1 == $req_action ) {
			?>
			<h1><?php _e( 'Logs', 'w4-loggable' ); ?></h1>
			<?php do_action( 'w4_loggable/admin_page/notices' ); ?>
			<div class="W4_Loggable_admin_content">
			<?php
	  			include_once( W4_LOGS_DIR . 'includes/Admin/Partial/Log/list-table.php' );
			?></div><?php
		}

		do_action( 'W4_loggable/config_page/template_after/' );

		?></div><?php
	}

	public function admin_menu() {
		// access capability
		$access_cap = apply_filters( 'W4_loggable/access_cap/logs', 'manage_options' );
		// register menu
		$admin_page = add_submenu_page(
			'tools.php',
			__( 'Logs', 'w4-loggable' ),
			__( 'Logs', 'w4-loggable' ),
			$access_cap,
			'w4-loggable-logs',
			[$this, 'render_page']
		 );

		add_action( "admin_print_styles-{$admin_page}"	 , [$this, 'print_scripts'] );
		add_action( "load-{$admin_page}"						 , [$this, 'load_page'] );
		add_action( "load-{$admin_page}"						 , [$this, 'handle_actions'] );
	}

	public function print_scripts() {
		wp_enqueue_style( ['select2', 'W4_loggable-admin-main'] );
		wp_enqueue_script( ['select2', 'W4_loggable-admin-main'] );
		do_action( 'W4_loggable/config_page/print_styles/logs' );
	}
}
