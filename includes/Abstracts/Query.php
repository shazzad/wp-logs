<?php
/**
 * A Simple Query Class
 */
namespace Shazzad\WpLogs\Abstracts;

use WP_Error;
use Shazzad\WpLogs\DbAdapter;

abstract class Query {
	protected $table;
	protected $columns = [];
	protected $primary_column = null;
	protected $relatives = [];
	protected $data = null;
	protected $paginations = [];

	// Add the following properties to avoid deprecated warnings
	protected $output;
	protected $_select;
	protected $_fields;
	protected $_found_rows;
	protected $_join;
	protected $_where;
	protected $_groupby;
	protected $_order;
	protected $_limit;

	public $query_args;
	public $request;
	public $errors;
	public $use_cache = false;

	public $cache_ttl = 30;

	public $found_items = 0;
	public $limit = '';
	public $page = 1;
	public $max_num_pages = 1;

	public $use_found_rows = true;

	public function __construct( $query_args ) {
		$this->query_args = $query_args;
		$this->reset();
		$this->parse_query_vars();
	}

	// Reset all query properties
	public function reset() {
		$this->output      = "";
		$this->_select     = "";
		$this->_fields     = "";
		$this->_found_rows = "";
		$this->_join       = "";
		$this->_where      = "";
		$this->_groupby    = "";
		$this->_order      = "";
		$this->_limit      = "";
	}

	// Set a query argument
	public function set( $key, $val ) {
		$this->query_args[$key] = $val;
	}

	// Get a query argument with a default value
	public function get( $key, $default = '' ) {
		return array_key_exists( $key, $this->query_args ) ? $this->query_args[$key] : $default;
	}

	// Parse and validate query variables
	public function parse_query_vars() {
		if ( ! is_array( $this->query_args ) ) {
			$this->query_args = [];
		}

		if ( '' == $this->table ) {
			$this->errors[] = "Table Not Defined";
			return;
		}

		if ( '' == $this->get( 'order' ) || ! in_array( strtoupper( $this->get( 'order' ) ), [ 'ASC', 'DESC' ] ) ) {
			$this->set( 'order', "ASC" );
		}

		$this->set( 'order', strtoupper( $this->get( 'order' ) ) );

		if ( '' != $this->get( 'paged' ) ) {
			$this->set( 'page', $this->get( 'paged' ) );
		}

		if ( '' == $this->get( 'page' ) ) {
			$this->set( 'page', $this->page );
		} else {
			$this->page = $this->get( 'page' ) < 1 ? 1 : $this->get( 'page' );
		}

		if ( '' != $this->get( 'limit' ) ) {
			$this->limit = absint( $this->get( 'limit' ) );
		} elseif ( '' != $this->get( 'per_page' ) ) {
			$this->limit = absint( $this->get( 'per_page' ) );
		}

		if ( '' != $this->get( 'method' ) ) {
			$this->set( 'method', strtolower( $this->get( 'method' ) ) );
		} else {
			$this->set( 'method', 'get_results' );
		}

		$this->output = $this->get( 'output' ) ? $this->get( 'output' ) : OBJECT;
	}

	// Build the default query based on the query arguments
	public function build_default_query() {
		$this->_select = "SELECT";
		$this->_join   = " FROM $this->table AS TB";
		$this->_where  = " WHERE 1=1";

		if ( '' != $this->get( 'column' ) ) {
			$this->_fields .= " TB." . $this->get( 'column' );
		} elseif ( '' != $this->get( 'columns' ) ) {
			$this->_fields .= " TB." . implode( ", TB.", $this->get( 'columns' ) );
		} elseif ( $this->get( 'method' ) == 'count_row' ) {
			$this->_fields .= " COUNT( * )";
		} else {
			$this->_fields .= " TB.*";
		}

		$search_args = [];

		foreach ( $this->columns as $column => $args ) {
			switch ( $args['type'] ) {
				case 'text':
				case 'varchar':
				case 'price':
					$this->parse_text_fields( [
						$column => "TB.{$column}"
					] );
					if ( $this->get( "{$column}__like" ) ) {
						$search_args[$column] = $this->get( "{$column}__like" );
					}
					break;
				case 'interger':
					$this->parse_interger_fields( [
						$column => "TB.{$column}"
					] );
					$this->parse_interger_fields( [
						"{$column}__not" => "TB.{$column}"
					], 'NOT IN' );
					break;
				default:
					break;
			}
		}

		if ( $this->get( 'sb' ) != '' ) {
			$search_args[$this->get( 'sb' )] = $this->get( 's' );
		} else {
			foreach ( $this->columns as $column => $args ) {
				if ( ! empty( $search_args[$column] ) ) {
					continue;
				}

				if ( isset( $args['searchable'] ) && $args['searchable'] ) {
					$keyword = $this->get( 's' );

					if ( in_array( $args['type'], [ 'text', 'varchar' ] ) ) {
						$search_args[$column] = $keyword;
					} elseif ( is_numeric( $keyword ) && in_array( $args['type'], [ 'interger', 'number' ] ) ) {
						$search_args[$column] = $keyword;
					}
				}
			}
		}

		if ( ! empty( $search_args ) ) {
			$this->parse_search_fields( $search_args );
		}

		if ( '' != $this->get( 'groupby' ) ) {
			$groupby        = $this->get( 'groupby' );
			$this->_groupby .= " GROUP BY {$groupby}";
		}

		if ( '' != $this->get( 'orderby' ) ) {
			$order        = $this->get( 'order' );
			$orderby      = $this->get( 'orderby' );
			$this->_order .= " ORDER BY {$orderby} $order";
		}

		if ( '' != $this->limit ) {
			if ( '' == $this->get( 'offset' ) ) {
				$start        = ( $this->page - 1 ) * $this->limit . ', ';
				$this->_limit .= ' LIMIT ' . $start . $this->limit;
			} else {
				$this->set( 'offset', absint( $this->get( 'offset' ) ) );
				$start        = $this->get( 'offset' ) . ', ';
				$this->_limit .= ' LIMIT ' . $start . $this->limit;
			}
		}

		if ( '' != $this->limit && $this->use_found_rows ) {
			$this->_found_rows = " SQL_CALC_FOUND_ROWS";
		}
	}

	// Execute the query
	public function query() {
		// Method to execute the query should be implemented here.
	}

	// Parse search fields to build the search WHERE clause
	public function parse_search_fields( $args = [], $relation = 'OR' ) {
		if ( empty( $args ) ) {
			return;
		}

		$args = array_filter( $args );

		$search_where = '';

		foreach ( $args as $column => $term ) {
			preg_match_all( '/".*?("|$)|((?<=[\s",+])|^)[^\s",+]+/', $term, $terms );

			$search_terms = [];
			if ( is_array( $terms[0] ) ) {
				foreach ( $terms[0] as $s ) {
					$search_terms[] = trim( $s, "\"'\n\r " );
				}
			} else {
				$search_terms[] = $terms[0];
			}

			$n         = '%';
			$searchand = '';
			$search    = '';

			$escap_words = [ '-', '_' ];

			foreach ( (array) $search_terms as $term ) {
				if ( in_array( $term, $escap_words ) ) {
					continue;
				}

				$term      = DbAdapter::esc_sql( DbAdapter::esc_like( $term ) );
				$search .= "{$searchand}($column LIKE '{$n}{$term}{$n}')";
				$searchand = ' AND ';
			}

			if ( ! empty( $search ) ) {
				if ( ! empty( $search_where ) ) {
					$search_where .= " {$relation} ";
				}

				$search_where .= "({$search})";
			}
		}

		if ( ! empty( $search_where ) ) {
			$this->_where .= " AND ({$search_where})";
		}
	}

	// Parse integer fields to build the search WHERE clause
	public function parse_interger_fields( $args = [], $compare = '' ) {
		if ( empty( $args ) ) {
			return;
		}

		foreach ( $args as $request => $column ) {
			if ( is_numeric( $request ) ) {
				$request = $column;
			}

			if ( '__empty__' == $this->get( $request ) ) {
				$this->_where .= " AND $column = ''";
			} elseif ( '__not_empty__' == $this->get( $request ) ) {
				$this->_where .= " AND $column <> ''";
			}

			if ( '' != $this->get( $request ) ) {
				$var = $this->get( $request );

				if ( is_array( $var ) ) {
					$_compare     = ! $compare ? "IN" : $compare;
					$this->_where .= " AND {$column} {$_compare} ( " . implode( ',', array_map( 'intval', $var ) ) . " )";
				} elseif ( is_numeric( $var ) ) {
					$_compare     = ! $compare ? "=" : $compare;
					$this->_where .= " AND {$column} {$_compare} " . intval( $var );
				}
			}
		}
	}

	// Parse text fields to build the search WHERE clause
	public function parse_text_fields( $args = [], $compare = '' ) {
		if ( empty( $args ) ) {
			return;
		}

		foreach ( $args as $request => $column ) {
			if ( is_numeric( $request ) ) {
				$request = $column;
			}

			if ( '__empty__' == $this->get( $request ) ) {
				$this->_where .= " AND $column = ''";
			} elseif ( '__not_empty__' == $this->get( $request ) ) {
				$this->_where .= " AND $column <> ''";
			} elseif ( '' != $this->get( $request ) ) {
				$var = $this->get( $request );

				if ( is_array( $var ) && ! empty( $var ) ) {
					$_compare     = ! $compare ? "IN" : $compare;
					$this->_where .= " AND {$column} {$_compare} ( \"" . implode( '\",\"', array_map( 'esc_sql', $var ) ) . "\" )";
				} else {
					$_compare     = ! $compare ? "=" : $compare;
					$this->_where .= " AND {$column} {$_compare} '" . esc_sql( $var ) . "'";
				}
			}
		}
	}

	// Parse sortable fields for ordering
	public function parse_sortable_fields( $args = [] ) {
		if ( empty( $args ) ) {
			return;
		}

		foreach ( $args as $request => $reset ) {
			if ( $request == $this->get( 'orderby' ) ) {
				$this->set( 'orderby', $reset );
			}
			if ( $request == $this->get( 'sb' ) ) {
				$this->set( 'sb', $reset );
			}
		}
	}

	// Fetch the query results
	public function fetch_results() {
		if ( ! empty( $this->errors ) ) {
			$error_obj = new WP_Error();
			foreach ( $this->errors as $error ) {
				$error_obj->add( 'error', $error );
			}
			return $error_obj;
		}

		if ( '' == $this->get( 'method' ) && '' != $this->get( 'column' ) ) {
			$this->set( 'method', 'get_col' );
		}

		if ( ! in_array( $this->get( 'method' ), [ 'get_row', 'get_var', 'get_col', 'count_row' ] ) ) {
			$this->set( 'method', 'get_results' );
		}

		// Enable cache
		if ( $this->use_cache ) {
			$request_hash = md5( $this->request );

			$result = wp_cache_get( "result_{$request_hash}" );
			$attrs  = wp_cache_get( "attrs_{$request_hash}" );

			if ( false !== $result && false !== $attrs ) {
				$this->data          = $result;
				$this->found_items   = $attrs['found_items'];
				$this->max_num_pages = $attrs['max_num_pages'];

				return true;
			}
		}

		if ( $this->get( 'method' ) == 'get_col' ) {
			$result = DbAdapter::get_col( $this->request );
		} elseif ( $this->get( 'method' ) == 'count_row' || $this->get( 'method' ) == 'get_var' ) {
			$result = DbAdapter::get_var( $this->request );
		} elseif ( $this->get( 'method' ) == 'get_row' ) {
			$result = DbAdapter::get_row( $this->request, $this->output );
		} else {
			$result = DbAdapter::get_rows( $this->request, $this->output );
		}

		$this->data = $result;

		if ( '' != $this->limit ) {
			if ( $this->use_found_rows ) {
				$this->found_items = (int) DbAdapter::get_found_rows();
			} else {
				$this->found_items = (int) DbAdapter::get_var( $this->get_count_query() );
			}

			$this->max_num_pages = ceil( $this->found_items / $this->limit );
		} else {
			$this->found_items   = is_array( $result ) || is_object( $result ) ? count( $result ) : 0;
			$this->max_num_pages = 1;
		}

		$this->paginations = [
			'current' => $this->get( 'paged' ),
			'items'   => $this->found_items,
			'pages'   => $this->max_num_pages
		];

		// If cache enabled, keep the data on cache
		if ( $this->use_cache ) {
			wp_cache_set( "result_{$request_hash}", $this->data, '', $this->cache_ttl );
			wp_cache_set( "attrs_{$request_hash}", [
				'found_items'   => $this->found_items,
				'max_num_pages' => $this->max_num_pages
			], '', $this->cache_ttl );
		}

		return true;
	}

	// Get the query results
	public function get_results() {
		return $this->data;
	}
}
