<?php
namespace Shazzad\WpLogs;

use Exception;

/**
 * Database adapter
 * 
 * @package Shazzad\WpLogs
 */

class DbAdapter
{
	public static function prefix_table($table)
	{
		global $wpdb;
		if (in_array($table, array('posts', 'postmeta'))) {
			return $wpdb->prefix . $table;
		} else {
			return $wpdb->prefix . 'swpl_' . $table;
		}
	}

	public static function insert($table, $data)
	{
		global $wpdb;
		if (!$wpdb->insert($table, $data)) {
			throw new Exception('Unable to insert into database');
		}
		return $wpdb->insert_id;
	}

	public static function update($table, $data, $where)
	{
		global $wpdb;
		return $wpdb->update($table, $data, $where);
	}

	public static function delete($table, $where)
	{
		global $wpdb;
		return $wpdb->delete($table, $where);
	}

	public static function insert_id()
	{
		global $wpdb;
		return $wpdb->insert_id;
	}

	public static function get_row($query, $output = 'ARRAY_A')
	{
		global $wpdb;
		return $wpdb->get_row($query, $output);
	}

	public static function get_rows($query, $output = 'ARRAY_A')
	{
		global $wpdb;
		return $wpdb->get_results($query, $output);
	}

	public static function get_col($query)
	{
		global $wpdb;
		return $wpdb->get_col($query);
	}

	public static function get_var($query)
	{
		global $wpdb;
		return $wpdb->get_var($query);
	}

	public static function query($query)
	{
		global $wpdb;
		return $wpdb->query($query);
	}

	public static function get_found_rows()
	{
		global $wpdb;
		return $wpdb->get_var('SELECT FOUND_ROWS()');
	}

	public static function esc_like($query)
	{
		global $wpdb;
		return $wpdb->esc_like($query);
	}

	public static function esc_sql($query)
	{
		global $wpdb;
		return esc_sql($query);
	}
}