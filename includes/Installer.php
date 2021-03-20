<?php
namespace W4dev\Loggable;

/**
 * Core Environment
 * @package W4dev\Loggable
 */


class Installer
{
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

		$logs = \W4dev\Loggable\DbAdapter::prefix_table( 'logs' );
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
	 * @since 2.8
	 * update tables
	*/

	public static function update_tables() {
		# self::update_tables_282();
	}

	/*
	 * @since 2.8
	 * update tables
	*/

	public static function update_tables_282() {
	}
}
