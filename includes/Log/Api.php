<?php
namespace Shazzad\WpLogs\Log;

use Shazzad\WpLogs\DbAdapter;
use Shazzad\WpLogs\Abstracts\CrudApi;

class Api extends CrudApi {
	public function __construct() {
		$this->key              = 'log';
		$this->name             = __( 'Log', 'swpl' );
		$this->key_plural       = 'logs';
		$this->name_plural      = __( 'Logs', 'swpl' );
		$this->model_class_name = 'Shazzad\WpLogs\Log\Data';
		$this->query_class_name = 'Shazzad\WpLogs\Log\Query';
	}

	public function delete_all( $data = [] ) {
		global $wpdb;

		$table = DbAdapter::prefix_table( 'logs' );

		// Use direct sql to delete logs rather than modular read/delete
		// There could be high amount of entries.
		$sql = "DELETE FROM {$table}";

		$wpdb->query( $sql );

		swpl_clear_cache();

		return [
			'message' => __( 'Logs deleted', 'swpl' )
		];
	}
}