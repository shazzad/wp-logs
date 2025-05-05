<?php
namespace Shazzad\WpLogs;

use Exception;

/**
 * Database adapter for handling WordPress database operations.
 * 
 * This class provides a wrapper for WordPress database functions with
 * consistent error handling and table name prefixing.
 * 
 * @package Shazzad\WpLogs
 * @since 1.0.0
 */

class DbAdapter {

	/**
	 * Prefixes a table name with the WordPress prefix and plugin prefix if needed.
	 *
	 * @since 1.0.0
	 * @param string $table The table name to prefix.
	 * @return string The prefixed table name.
	 */
	public static function prefix_table( $table ) {
		global $wpdb;

		if ( in_array( $table, [ 'logs', 'requests' ] ) ) {
			return "{$wpdb->prefix}swpl_{$table}";
		} else {
			return "{$wpdb->prefix}{$table}";
		}
	}

	/**
	 * Inserts data into a database table.
	 *
	 * @since 1.0.0
	 * @param string $table The table name to insert data into.
	 * @param array  $data  The data to insert (name => value pairs).
	 * @return int The ID of the inserted row.
	 * @throws Exception When unable to insert data.
	 */
	public static function insert( $table, $data ) {
		global $wpdb;

		// Enable error logging.
		// $wpdb->show_errors();

		if ( ! $wpdb->insert( $table, $data ) ) {
			// Log the error message.
			// $error_message = $wpdb->last_error;
			// $error_code    = $wpdb->last_error;
			// $error_query   = $wpdb->last_query;
			// $error_data    = $wpdb->last_result;
			// $error_message = "Error Code: {$error_code}, Error Message: {$error_message}, Query: {$error_query}, Data: " . json_encode( $error_data );

			// throw new Exception( "Unable to insert into {$table}: {$error_message}. Data:" . json_encode( $data ) );

			throw new Exception( "Unable to insert into {$table}" );
		}
		return $wpdb->insert_id;
	}

	/**
	 * Updates data in a database table.
	 *
	 * @since 1.0.0
	 * @param string $table The table to update.
	 * @param array  $data  The data to update (name => value pairs).
	 * @param array  $where The where clause for the update.
	 * @return int|false The number of rows updated, or false on error.
	 */
	public static function update( $table, $data, $where ) {
		global $wpdb;
		return $wpdb->update( $table, $data, $where );
	}

	/**
	 * Deletes data from a database table.
	 *
	 * @since 1.0.0
	 * @param string $table The table to delete from.
	 * @param array  $where The where clause for the delete.
	 * @return int|false The number of rows deleted, or false on error.
	 */
	public static function delete( $table, $where ) {
		global $wpdb;
		return $wpdb->delete( $table, $where );
	}

	/**
	 * Gets the ID of the last inserted row.
	 *
	 * @since 1.0.0
	 * @return int The ID of the last inserted row.
	 */
	public static function insert_id() {
		global $wpdb;
		return $wpdb->insert_id;
	}

	/**
	 * Retrieves a single row from the database.
	 *
	 * @since 1.0.0
	 * @param string $query  SQL query to execute.
	 * @param string $output Optional. The required return type. One of ARRAY_A, ARRAY_N, OBJECT, or OBJECT_K.
	 * @return array|object|null Database query result in format specified by $output or null on failure.
	 */
	public static function get_row( $query, $output = 'ARRAY_A' ) {
		global $wpdb;
		return $wpdb->get_row( $query, $output );
	}

	/**
	 * Retrieves multiple rows from the database.
	 *
	 * @since 1.0.0
	 * @param string $query  SQL query to execute.
	 * @param string $output Optional. The required return type. One of ARRAY_A, ARRAY_N, OBJECT, or OBJECT_K.
	 * @return array|object|null Database query result in format specified by $output or null on failure.
	 */
	public static function get_rows( $query, $output = 'ARRAY_A' ) {
		global $wpdb;
		return $wpdb->get_results( $query, $output );
	}

	/**
	 * Retrieves a single column from the database.
	 *
	 * @since 1.0.0
	 * @param string $query SQL query to execute.
	 * @return array|null Database query result or null on failure.
	 */
	public static function get_col( $query ) {
		global $wpdb;
		return $wpdb->get_col( $query );
	}

	/**
	 * Retrieves a single variable from the database.
	 *
	 * @since 1.0.0
	 * @param string $query SQL query to execute.
	 * @return string|null Database query result or null on failure.
	 */
	public static function get_var( $query ) {
		global $wpdb;
		return $wpdb->get_var( $query );
	}

	/**
	 * Executes a query on the database.
	 *
	 * @since 1.0.0
	 * @param string $query SQL query to execute.
	 * @return int|false Number of rows affected, or false on error.
	 */
	public static function query( $query ) {
		global $wpdb;
		return $wpdb->query( $query );
	}

	/**
	 * Retrieves the number of rows found by the last query.
	 *
	 * @since 1.0.0
	 * @return int Number of rows found by the last query.
	 */
	public static function get_found_rows() {
		global $wpdb;
		return (int) $wpdb->get_var( 'SELECT FOUND_ROWS()' );
	}

	/**
	 * Escapes a string for use in a LIKE query.
	 *
	 * @since 1.0.0
	 * @param string $query The string to escape.
	 * @return string The escaped string.
	 */
	public static function esc_like( $query ) {
		global $wpdb;
		return $wpdb->esc_like( $query );
	}

	/**
	 * Escapes a string for use in an SQL query.
	 *
	 * @since 1.0.0
	 * @param string $query The string to escape.
	 * @return string The escaped string.
	 */
	public static function esc_sql( $query ) {
		global $wpdb;
		return esc_sql( $query );
	}
}