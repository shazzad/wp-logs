<?php
/**
 * Core Environment
 * 
 * @package Shazzad\WpLogs
 */
namespace Shazzad\WpLogs;

class Installer
{
	public static function rename_tables() 
	{
		global $wpdb;

		$old_logs_table = $wpdb->prefix . 'w4_loggable_logs';
		$new_logs_table = DbAdapter::prefix_table( 'logs' );

		if ( $wpdb->get_var( "SHOW TABLES LIKE '$new_logs_table'" ) != $new_logs_table ) {
			if ( $wpdb->get_var( "SHOW TABLES LIKE '$old_logs_table'" ) === $old_logs_table ) {
				$wpdb->query("ALTER TABLE `$old_logs_table` RENAME TO `$new_logs_table`");
			}
		}
	}

	public static function install_tables()
	{
		global $wpdb;

		$sql = array();

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
				level VARCHAR( 9 ) NOT NULL,
				source VARCHAR( 200 ) NOT NULL,
				message LONGTEXT NOT NULL,
				context LONGTEXT NULL DEFAULT '',
				PRIMARY KEY  ( id )
			 ) {$charset_collate};";
		}

		if ( ! empty( $sql ) ) {
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			dbDelta( $sql );
		}
	}

	/*
	 * Update tables
	 * 
	 * @since 2.8
	*/
	public static function update_tables() {
	}
}
