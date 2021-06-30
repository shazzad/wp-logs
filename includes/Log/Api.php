<?php
namespace Shazzad\WpLogs\Log;

use WP_Error;
use Shazzad\WpLogs\Utils;
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
		$query_args = array();

		if ( ! empty( $data['menu_item'] ) ) {
			$menu_item = Utils::get_menu_item( $data['menu_item'] );

			if ( $menu_item && isset( $menu_item['sources'] ) ) {
				$sources = $menu_item['sources'];

				$query_args['source'] = $sources;
			}
		}

		$query = new Query( $query_args );
		$query->query();
		$items = $query->get_objects();

		if ( empty( $items ) ) {
			new WP_Error( 'api_error', __( 'No logs to delete', 'shazzad-wp-logs' ) );
		}

		foreach ( $items as $item ) {
			$item->delete();
		}

		return array(
			'message' => sprintf( __( '%d logs deleted', 'shazzad-wp-logs' ), $query->found_items )
		);
	}
}
