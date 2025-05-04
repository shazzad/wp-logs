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
				'type'       => 'interger',
				'searchable' => true
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
			'request_method'   => [
				'type' => 'text',
			],
			'request_hostname' => [
				'type' => 'text',
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
