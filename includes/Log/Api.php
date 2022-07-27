<?php
namespace Shazzad\WpLogs\Log;

use WP_Error;
use Shazzad\WpLogs\Utils;
use Shazzad\WpLogs\DbAdapter;
use Shazzad\WpLogs\AbstractCrudApi;

class Api extends AbstractCrudApi
{
	public function __construct()
	{
		$this->key = 'log';
		$this->name = __('Log', 'shazzad_wp_logs');
		$this->key_plural = 'logs';
		$this->name_plural = __('Logs', 'shazzad_wp_logs');
		$this->model_class_name = 'Shazzad\WpLogs\Log\Data';
		$this->query_class_name = 'Shazzad\WpLogs\Log\Query';
	}

	public function delete_all( $data = array() ) {
		global $wpdb;

		$sources = array();

		if ( ! empty( $data['menu_item'] ) ) {
			$menu_item = Utils::get_menu_item( $data['menu_item'] );

			if ( $menu_item && isset( $menu_item['sources'] ) ) {
				$sources = $menu_item['sources'];
			}
		}

		$table = DbAdapter::prefix_table('logs');

		// Use direct sql to delete logs rather than modular read/delete
		// There could be high amount of entries.
		$sql = "DELETE FROM {$table}";

		if ( $sources ) {
			$sources = array_map( 'trim', $sources );
			$sql .= " WHERE source IN ('" . implode( "','", $sources ) . "')";
		}

		$wpdb->query( $sql );

		return array(
			'message' => __( 'Logs deleted', 'shazzad-wp-logs' )
		);
	}
}
