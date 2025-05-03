<?php
namespace Shazzad\WpLogs\Request;

use Shazzad\WpLogs\Utils;
use Shazzad\WpLogs\DbAdapter;
use Shazzad\WpLogs\Abstracts\CrudApi;

class Api extends CrudApi {
	public function __construct() {
		$this->key              = 'request';
		$this->name             = __( 'Request', 'shazzad-wp-logs' );
		$this->key_plural       = 'requests';
		$this->name_plural      = __( 'Requests', 'shazzad-wp-logs' );
		$this->model_class_name = 'Shazzad\WpLogs\Request\Data';
		$this->query_class_name = 'Shazzad\WpLogs\Request\Query';
	}

	public function delete_all( $data = [] ) {
		global $wpdb;

		$sources = [];

		if ( ! empty( $data['menu_item'] ) ) {
			$menu_item = Utils::get_menu_item( $data['menu_item'] );

			if ( $menu_item && isset( $menu_item['sources'] ) ) {
				$sources = $menu_item['sources'];
			}
		}

		$table = DbAdapter::prefix_table( 'requests' );

		// Use direct sql to delete logs rather than modular read/delete
		// There could be high amount of entries.
		$sql = "DELETE FROM {$table}";

		if ( $sources ) {
			$sources = array_map( 'trim', $sources );
			$sql .= " WHERE source IN ('" . implode( "','", $sources ) . "')";
		}

		$wpdb->query( $sql );

		swpl_clear_cache();

		return [
			'message' => __( 'Requests deleted', 'shazzad-wp-logs' )
		];
	}
}