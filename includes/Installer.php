<?php
/**
 * Core Environment
 * 
 * @package Shazzad\WpLogs
 */
namespace Shazzad\WpLogs;

use Shazzad\WpLogs\DbAdapter;

require_once __DIR__ . '/DbAdapter.php';

class Installer {

	public static function activate() {
		self::rename_tables();
		self::install_tables();
		self::update_tables();

		update_option( 'swpl_version', SWPL_VERSION );
	}

	public static function upgrade() {
		self::rename_tables();
		self::install_tables();
		self::update_tables();

		update_option( 'swpl_version', SWPL_VERSION );
	}

	public static function rename_tables() {
	}

	public static function install_tables() {
		global $wpdb;

		$sql = [];

		if ( ! empty( $wpdb->charset ) ) {
			$charset_collate = "DEFAULT CHARACTER SET {$wpdb->charset}";
			if ( ! empty( $wpdb->collate ) ) {
				$charset_collate .= " COLLATE {$wpdb->collate}";
			}
		} else {
			$charset_collate = "";
		}

		$logs = DbAdapter::prefix_table( 'logs' );

		if ( $wpdb->get_var( "SHOW TABLES LIKE '$logs'" ) != $logs ) {
			$sql[] = "CREATE TABLE {$logs} (
				id BIGINT( 20 ) UNSIGNED NOT NULL AUTO_INCREMENT,
				timestamp datetime NOT NULL,
				source VARCHAR( 200 ) NOT NULL,
				level VARCHAR( 9 ) NOT NULL,
				message TEXT NOT NULL,
				context LONGTEXT NULL DEFAULT '',
				PRIMARY KEY  ( id ),
				KEY source (source)
			 ) {$charset_collate};";
		}

		// Create requests table
		$requests = DbAdapter::prefix_table( 'requests' );

		if ( $wpdb->get_var( "SHOW TABLES LIKE '$requests'" ) != $requests ) {
			$sql[] = "CREATE TABLE {$requests} (
				id BIGINT( 20 ) UNSIGNED NOT NULL AUTO_INCREMENT,
				timestamp datetime NOT NULL,
				source VARCHAR( 200 ) NOT NULL,
				request_method VARCHAR( 10 ) NOT NULL,
				request_url VARCHAR( 255 ) NOT NULL,
				request_hostname VARCHAR( 100 ) NOT NULL,
				request_headers TEXT NULL DEFAULT '',
				request_payload TEXT NULL DEFAULT '',
				response_code SMALLINT NULL,
				response_size BIGINT NULL,
				response_time FLOAT NULL,
				response_headers TEXT NULL DEFAULT '',
				response_data LONGTEXT NULL DEFAULT '',
				PRIMARY KEY  ( id ),
				KEY source (source),
				KEY request_hostname (request_hostname),
				KEY response_code (response_code)
			 ) {$charset_collate};";
		}

		// return $sql;

		// print_r( $sql );

		if ( ! empty( $sql ) ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $sql );
		}
	}

	/*
	 * Update tables
	 * 
	 * @since 2.8
	 */
	public static function update_tables() {
		// global $wpdb;

		// $logs_table      = DbAdapter::prefix_table( 'logs' );
		// $logs_table_cols = $wpdb->get_col( "DESC {$logs_table}", 0 );

		// if ( in_array( 'message', $logs_table_cols ) ) {
		// 	$wpdb->query( "ALTER TABLE {$logs_table} CHANGE `message` `message` TEXT NOT NULL" );
		// }

		// if ( in_array( 'level', $logs_table_cols ) ) {
		// 	$wpdb->query( "ALTER TABLE {$logs_table} CHANGE `level` `group` VARCHAR( 9 ) NOT NULL" );
		// }

		// if ( in_array( 'context', $logs_table_cols ) ) {
		// 	$wpdb->query( "ALTER TABLE {$logs_table} CHANGE `context` `data` LONGTEXT NULL DEFAULT ''" );
		// }
	}
}