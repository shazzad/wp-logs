<?php
namespace Shazzad\WpLogs\Request;

use Shazzad\WpLogs\Abstracts\Query as AbstractQuery;
use Shazzad\WpLogs\DbAdapter;

class Query extends AbstractQuery {

	public $use_found_rows = false;

	public function __construct( $args = [] ) {
		$this->table = DbAdapter::prefix_table( 'requests' );

		$this->columns = [
			'id'               => [
				'type' => 'interger'
			],
			'source'           => [
				'type'       => 'varchar',
				'searchable' => true
			],
			'method'           => [
				'type'       => 'method',
				'searchable' => true
			],
			'request_url'      => [
				'type'       => 'text',
				'searchable' => true
			],
			'request_hostname' => [
				'type'       => 'text',
				'searchable' => false
			],
			'response_code'    => [
				'type' => 'interger'
			]
		];

		parent::__construct( $args );
	}

	public function query() {
		if ( ! empty( $this->errors ) ) {
			return;
		}

		$this->build_default_query();

		// Build the request.
		$this->request = $this->_select
			. $this->_found_rows
			. $this->_fields
			. $this->_join
			. $this->_where
			. $this->_groupby
			. $this->_order
			. $this->_limit;

		// Fetch results.
		$this->fetch_results();
	}

	public function get_count_query() {
		return $this->_select
			. $this->_found_rows
			. " COUNT( * )"
			. $this->_join
			. $this->_where
			. $this->_groupby;
	}

	public function get_objects() {
		$objects = [];
		foreach ( $this->get_results() as $resut ) {
			$objects[] = Data::load( $resut );
		}

		return $objects;
	}
}
