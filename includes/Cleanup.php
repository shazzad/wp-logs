<?php
/**
 * Cleanup functionality for logs and requests
 * 
 * @package Shazzad\WpLogs
 */
namespace Shazzad\WpLogs;

/**
 * Cleanup class
 * 
 * Handles automatic cleanup of logs and requests based on configured thresholds
 */
class Cleanup {

	/**
	 * Set up cleanup hooks and scheduled events
	 *
	 * @return void
	 */
	public static function setup() {
		add_action( 'init', [ __CLASS__, 'register_events' ] );
		add_action( 'swpl_cleanup_logs', [ __CLASS__, 'cleanup_logs' ] );
		add_action( 'swpl_cleanup_requests', [ __CLASS__, 'cleanup_requests' ] );
	}

	/**
	 * Register cleanup cron jobs
	 * 
	 * Schedules hourly cleanup events for both logs and requests if not already scheduled
	 *
	 * @return void
	 */
	public static function register_events() {
		if ( ! wp_next_scheduled( 'swpl_cleanup_logs' ) ) {
			wp_schedule_event( time(), 'hourly', 'swpl_cleanup_logs' );
		}

		if ( ! wp_next_scheduled( 'swpl_cleanup_requests' ) ) {
			wp_schedule_event( time(), 'hourly', 'swpl_cleanup_requests' );
		}
	}

	/**
	 * Clean up logs based on threshold
	 * 
	 * Deletes older logs when they exceed the configured maximum threshold
	 *
	 * @return void
	 */
	public static function cleanup_logs() {
		$retention_days = (int) get_option( 'swpl_log_retention_days', 0 );

		if ( ! $retention_days ) {
			return;
		}

		global $wpdb;

		$table = DbAdapter::prefix_table( 'logs' );

		// Delete older logs than the retention days.
		$sql = "DELETE FROM $table WHERE timestamp < DATE_SUB(NOW(), INTERVAL %d DAY)";

		$wpdb->query( $wpdb->prepare( $sql, $retention_days ) );
	}

	/**
	 * Clean up requests based on threshold
	 * 
	 * Deletes older requests when they exceed the configured maximum threshold
	 *
	 * @return void
	 */
	public static function cleanup_requests() {
		$retention_days = (int) get_option( 'swpl_request_retention_days', 0 );

		if ( ! $retention_days ) {
			return;
		}

		global $wpdb;

		$table = DbAdapter::prefix_table( 'requests' );

		// Delete older requests than the retention days.
		$sql = "DELETE FROM $table WHERE timestamp < DATE_SUB(NOW(), INTERVAL %d DAY)";

		$wpdb->query( $wpdb->prepare( $sql, $retention_days ) );
	}
}