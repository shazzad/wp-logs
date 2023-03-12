<?php
namespace Shazzad\WpLogs\Log;

use Shazzad\WpLogs\AbstractQuery;
use Shazzad\WpLogs\DbAdapter;

class Query extends AbstractQuery
{
	public function __construct($args = array())
	{
		global $wpdb;

		$this->table = DbAdapter::prefix_table('logs');

		$this->columns = array(
			'id'        => array(
				'type' => 'interger'
			),
			'level'     => array(
				'type'       => 'varchar',
				'searchable' => true
			),
			'source'    => array(
				'type'       => 'varchar',
				'searchable' => true
			),
			'message'   => array(
				'type'       => 'text',
				'searchable' => true
			),
			'context'   => array(
				'type'       => 'text',
				'searchable' => false
			),
			'timestamp' => array(
				'type' => 'datetime'
			)
		);

		parent::__construct($args);
	}

	public function query()
	{
		if (!empty($this->errors)) {
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

	public function get_objects()
	{
		$objects = array();
		foreach ($this->get_results() as $resut) {
			$objects[] = Data::load($resut);
		}

		return $objects;
	}
}