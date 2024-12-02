<?php
namespace Shazzad\WpLogs\Log;

use Shazzad\WpLogs\Abstracts\Query as AbstractQuery;
use Shazzad\WpLogs\DbAdapter;

class Query extends AbstractQuery {

	public $use_found_rows = false;

	public function __construct( $args = [] ) {
		global $wpdb;

		$this->table = DbAdapter::prefix_table( 'logs' );

		$this->columns = [ 
			'id'        => [ 
				'type' => 'interger'
			],
			'level'     => [ 
				'type'       => 'varchar',
				'searchable' => true
			],
			'source'    => [ 
				'type'       => 'varchar',
				'searchable' => true
			],
			'message'   => [ 
				'type'       => 'text',
				'searchable' => true
			],
			'context'   => [ 
				'type'       => 'text',
				'searchable' => false
			],
			'timestamp' => [ 
				'type' => 'datetime'
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
