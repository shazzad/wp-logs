<?php
namespace Shazzad\WpLogs\Request;

use Shazzad\WpLogs\DbAdapter;
use Shazzad\WpLogs\Abstracts\CrudApi;

class Api extends CrudApi {
	public function __construct() {
		$this->key              = 'request';
		$this->name             = __( 'Request', 'swpl' );
		$this->key_plural       = 'requests';
		$this->name_plural      = __( 'Requests', 'swpl' );
		$this->model_class_name = 'Shazzad\WpLogs\Request\Data';
		$this->query_class_name = 'Shazzad\WpLogs\Request\Query';
	}

	public function delete_all( $data = [] ) {
		global $wpdb;

		$sources = [];

		$table = DbAdapter::prefix_table( 'requests' );

		// Use direct sql to delete logs rather than modular read/delete
		// There could be high amount of entries.
		$sql = "DELETE FROM {$table}";
		$wpdb->query( $sql );

		swpl_clear_cache();

		return [
			'message' => __( 'Requests deleted', 'swpl' )
		];
	}
}