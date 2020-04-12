<?php
namespace W4dev\Loggable\Log;

use W4dev\Loggable\AbstractQuery;

class Query extends AbstractQuery
{
	function __construct( $args = array() )
	{
		global $wpdb;

		$this->table = \W4dev\Loggable\DbAdapter::prefix_table('logs');
		$this->columns = array(
			'id' => array(
				'type' => 'interger'
			),
			'level' => array(
				'type' => 'varchar'
			),
			'source' => array(
				'type' => 'varchar'
			),
			'message' => array(
				'type' => 'varchar',
				'searchable' => true
			),
			'timestamp' => array(
				'type' => 'datetime'
			)
		);

		parent::__construct($args);
	}

	function query()
	{
		if( ! empty($this->errors) ) {
			return;
		}

		$this->build_default_query();

		// build the request
		$this->request = $this->_select
		. $this->_found_rows
		. $this->_fields
		. $this->_join
		. $this->_where
		. $this->_groupby
		. $this->_order
		. $this->_limit;

		// fetch results
		$this->fetch_results();
	}
}
