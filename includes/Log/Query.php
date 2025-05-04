<?php
namespace Shazzad\WpLogs\Log;

use Shazzad\WpLogs\Abstracts\Query as AbstractQuery;
use Shazzad\WpLogs\DbAdapter;

class Query extends AbstractQuery {

	public $use_found_rows = false;

	public function __construct( $args = [] ) {
		$this->table = DbAdapter::prefix_table( 'logs' );

		$this->columns = [
			'id'          => [
				'type'       => 'interger',
				'searchable' => true,
				'sortable'   => true
			],
			'message'     => [
				'type'       => 'text',
				'searchable' => true
			],
			'level'       => [
				'type' => 'varchar',
			],
			'source'      => [
				'type' => 'varchar',
			],
			'message_raw' => [
				'type' => 'text',
			],
			'context'     => [
				'type' => 'text',
			],
			'timestamp'   => [
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

	public function delete( $id ) {
		if ( empty( $id ) ) {
			return;
		}

		DbAdapter::delete( $this->table, array( 'id' => $id ) );
	}

	/**
	 * Create a new request.
	 * 
	 * @param array $data The data to create the request with.
	 */
	public function create( $data ) {
		if ( empty( $data ) ) {
			return;
		}

		// Insert the data into the database.
		$id = DbAdapter::insert( $this->table, $data );

		return $id;
	}


	/**
	 * Update a request.
	 *
	 * @param int   $id   The ID of the request to update.
	 * @param array $data The data to update the request with.
	 */
	public function update( $id, $data ) {
		if ( empty( $id ) || empty( $data ) ) {
			return;
		}

		DbAdapter::update( $this->table, $data, [ 'id' => $id ] );
	}
}
