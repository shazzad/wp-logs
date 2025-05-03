<?php
/**
 * Core Environment
 * 
 * @package Shazzad\WpLogs
 */
namespace Shazzad\WpLogs;

use Shazzad\WpLogs\DbAdapter;

require_once __DIR__ . '/DbAdapter.php';

/**
 * Handles the installation and upgrade processes for the plugin.
 *
 * This class is responsible for creating, updating, and managing the database tables
 * required by the plugin. It also handles activation and upgrade hooks.
 *
 * @package Shazzad\WpLogs
 */
class Installer {

    /**
     * Handles plugin activation tasks.
     *
     * This method is called when the plugin is activated. It ensures that the required
     * database tables are created or updated and sets the plugin version in the options table.
     *
     * @return void
     */
    public static function activate() {
        self::rename_tables();
        self::install_tables();
        self::update_tables();

        update_option( 'swpl_version', SWPL_VERSION );
    }

    /**
     * Handles plugin upgrade tasks.
     *
     * This method is called when the plugin is upgraded. It ensures that the database tables
     * are updated to match the latest schema and sets the plugin version in the options table.
     *
     * @return void
     */
    public static function upgrade() {
        self::rename_tables();
        self::install_tables();
        self::update_tables();

        update_option( 'swpl_version', SWPL_VERSION );
    }

    /**
     * Renames database tables if necessary.
     *
     * This method is a placeholder for any logic required to rename existing database tables
     * during plugin upgrades.
     *
     * @return void
     */
    public static function rename_tables() {
    }

    /**
     * Creates the required database tables.
     *
     * This method checks if the necessary database tables exist and creates them if they do not.
     * It uses the WordPress dbDelta function to handle table creation.
     *
     * @global wpdb $wpdb WordPress database abstraction object.
     * @return void
     */
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

    /**
     * Updates the database tables to match the latest schema.
     *
     * This method is a placeholder for any logic required to update existing database tables
     * during plugin upgrades.
     *
     * @since 2.8
     * @return void
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