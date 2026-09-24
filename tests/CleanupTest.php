<?php
/**
 * Tests for the batched retention purge.
 *
 * @package Shazzad\WpLogs
 */

use Shazzad\WpLogs\Cleanup;
use Shazzad\WpLogs\DbAdapter;

/**
 * Rows are written straight to the plugin tables with explicit GMT dates, so
 * each test controls exactly which rows fall before the retention cutoff.
 */
class CleanupTest extends WP_UnitTestCase {

	public function set_up() {
		parent::set_up();

		global $wpdb;
		// DELETE, not TRUNCATE: TRUNCATE commits the test transaction.
		$wpdb->query( 'DELETE FROM ' . DbAdapter::prefix_table( 'logs' ) );
		$wpdb->query( 'DELETE FROM ' . DbAdapter::prefix_table( 'requests' ) );

		update_option( 'swpl_log_retention_days', 7 );
		update_option( 'swpl_request_retention_days', 7 );

		wp_clear_scheduled_hook( 'swpl_cleanup_logs' );
		wp_clear_scheduled_hook( 'swpl_cleanup_requests' );
	}

	public function tear_down() {
		Cleanup::set_clock( null );
		parent::tear_down();
	}

	/**
	 * Insert $count log rows created $days_ago days ago.
	 */
	private function insert_logs( $count, $days_ago ) {
		global $wpdb;
		for ( $i = 0; $i < $count; $i++ ) {
			$wpdb->insert(
				DbAdapter::prefix_table( 'logs' ),
				[
					'date_created' => gmdate( 'Y-m-d H:i:s', time() - $days_ago * DAY_IN_SECONDS ),
					'source'       => 'test',
					'level'        => 'info',
					'message'      => "row $i, $days_ago days old",
					'context'      => '',
				]
			);
		}
	}

	/**
	 * Insert $count request rows created $days_ago days ago.
	 */
	private function insert_requests( $count, $days_ago ) {
		global $wpdb;
		for ( $i = 0; $i < $count; $i++ ) {
			$wpdb->insert(
				DbAdapter::prefix_table( 'requests' ),
				[
					'date_created'     => gmdate( 'Y-m-d H:i:s', time() - $days_ago * DAY_IN_SECONDS ),
					'request_method'   => 'GET',
					'request_url'      => 'https://example.org/' . $i,
					'request_hostname' => 'example.org',
				]
			);
		}
	}

	/**
	 * Count the rows the test inserted. A paused purge writes its own entry
	 * to the logs table (source "WP Logs"), which is not counted here.
	 */
	private function count_rows( $type ) {
		global $wpdb;
		$where = 'logs' === $type ? " WHERE source = 'test'" : '';
		return (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . DbAdapter::prefix_table( $type ) . $where );
	}

	private function batch_size( $size ) {
		add_filter(
			'swpl_purge_batch_size',
			function () use ( $size ) {
				return $size;
			}
		);
	}

	/**
	 * A clock that advances $step seconds each time it is read.
	 */
	private function ticking_clock( $step ) {
		$now = 0.0;
		Cleanup::set_clock(
			function () use ( &$now, $step ) {
				$current = $now;
				$now    += $step;
				return $current;
			}
		);
	}

	public function test_cron_hooks_are_wired_to_the_purge() {
		$this->assertNotFalse( has_action( 'swpl_cleanup_logs', [ Cleanup::class, 'cleanup_logs' ] ) );
		$this->assertNotFalse( has_action( 'swpl_cleanup_requests', [ Cleanup::class, 'cleanup_requests' ] ) );

		Cleanup::register_events();

		$this->assertSame( 'hourly', wp_get_schedule( 'swpl_cleanup_logs' ) );
		$this->assertSame( 'hourly', wp_get_schedule( 'swpl_cleanup_requests' ) );
	}

	public function test_logs_purge_deletes_only_expired_rows() {
		$this->insert_logs( 3, 10 );
		$this->insert_logs( 2, 1 );

		$result = Cleanup::cleanup_logs();

		$this->assertSame( 3, $result['deleted'] );
		$this->assertTrue( $result['complete'] );
		$this->assertSame( 2, $this->count_rows( 'logs' ) );
	}

	public function test_requests_purge_deletes_only_expired_rows() {
		$this->insert_requests( 4, 10 );
		$this->insert_requests( 1, 1 );

		$result = Cleanup::cleanup_requests();

		$this->assertSame( 4, $result['deleted'] );
		$this->assertTrue( $result['complete'] );
		$this->assertSame( 1, $this->count_rows( 'requests' ) );
	}

	public function test_purge_respects_each_tables_retention_setting() {
		update_option( 'swpl_log_retention_days', 3 );
		update_option( 'swpl_request_retention_days', 30 );
		$this->insert_logs( 1, 4 );
		$this->insert_logs( 1, 2 );
		$this->insert_requests( 2, 10 );

		$this->assertSame( 1, Cleanup::cleanup_logs()['deleted'] );
		$this->assertSame( 0, Cleanup::cleanup_requests()['deleted'] );
		$this->assertSame( 1, $this->count_rows( 'logs' ) );
		$this->assertSame( 2, $this->count_rows( 'requests' ) );
	}

	public function test_purge_deletes_in_batches_of_the_filtered_size() {
		$this->batch_size( 2 );
		$this->insert_logs( 5, 10 );
		$this->insert_logs( 1, 1 );

		$queries = [];
		$spy     = function ( $query ) use ( &$queries ) {
			if ( 0 === strpos( $query, 'DELETE FROM ' . DbAdapter::prefix_table( 'logs' ) ) ) {
				$queries[] = $query;
			}
			return $query;
		};
		add_filter( 'query', $spy );
		$result = Cleanup::cleanup_logs();
		remove_filter( 'query', $spy );

		$this->assertSame( 5, $result['deleted'] );
		$this->assertSame( 3, $result['batches'] );
		$this->assertTrue( $result['complete'] );
		$this->assertCount( 3, $queries );
		foreach ( $queries as $query ) {
			$this->assertStringContainsString( 'ORDER BY id ASC LIMIT 2', $query );
		}
		$this->assertSame( 1, $this->count_rows( 'logs' ) );
	}

	public function test_purge_deletes_oldest_rows_first() {
		$this->batch_size( 2 );
		$this->ticking_clock( 100 ); // Budget spent after the first batch.
		$this->insert_logs( 4, 10 );

		global $wpdb;
		$table = DbAdapter::prefix_table( 'logs' );
		$ids   = array_map( 'intval', $wpdb->get_col( "SELECT id FROM $table ORDER BY id ASC" ) );

		Cleanup::cleanup_logs();

		$left = array_map( 'intval', $wpdb->get_col( "SELECT id FROM $table WHERE source = 'test' ORDER BY id ASC" ) );
		$this->assertSame( array_slice( $ids, 2 ), $left );
	}

	public function test_invalid_batch_size_falls_back_to_default() {
		$this->batch_size( 0 );
		$this->insert_logs( 3, 10 );

		$result = Cleanup::cleanup_logs();

		$this->assertSame( 3, $result['deleted'] );
		$this->assertSame( 1, $result['batches'] );
		$this->assertSame( 5000, Cleanup::get_batch_size( 'logs' ) );
	}

	public function test_batch_size_filter_receives_the_table_type() {
		$seen = [];
		add_filter(
			'swpl_purge_batch_size',
			function ( $size, $type ) use ( &$seen ) {
				$seen[] = $type;
				return $size;
			},
			10,
			2
		);

		Cleanup::cleanup_logs();
		Cleanup::cleanup_requests();

		$this->assertSame( [ 'logs', 'requests' ], $seen );
	}

	public function test_purge_stops_when_the_time_budget_is_spent() {
		$this->batch_size( 1 );
		$this->ticking_clock( 8 ); // Start at 0s; batches end at 8s, 16s, 24s.
		$this->insert_logs( 10, 10 );

		$result = Cleanup::cleanup_logs();

		$this->assertSame( 3, $result['batches'] );
		$this->assertSame( 3, $result['deleted'] );
		$this->assertFalse( $result['complete'] );
		$this->assertSame( 7, $this->count_rows( 'logs' ) );
	}

	public function test_time_budget_is_filterable() {
		$this->batch_size( 1 );
		$this->ticking_clock( 8 );
		add_filter(
			'swpl_purge_time_budget',
			function () {
				return 50;
			}
		);
		$this->insert_logs( 10, 10 );

		// Batches end at 8, 16, 24, 32, 40, 48, 56 seconds.
		$this->assertSame( 7, Cleanup::cleanup_logs()['batches'] );
	}

	public function test_an_interrupted_purge_continues_on_the_next_run() {
		$this->batch_size( 1 );
		$this->ticking_clock( 8 );
		$this->insert_requests( 5, 10 );
		$this->insert_requests( 1, 1 );

		$first = Cleanup::cleanup_requests();
		$this->assertFalse( $first['complete'] );

		Cleanup::set_clock( null );
		$second = Cleanup::cleanup_requests();

		$this->assertTrue( $second['complete'] );
		$this->assertSame( 5, $first['deleted'] + $second['deleted'] );
		$this->assertSame( 1, $this->count_rows( 'requests' ) );
	}

	public function test_an_interrupted_purge_schedules_a_follow_up_run() {
		$this->batch_size( 1 );
		$this->ticking_clock( 100 );
		$this->insert_logs( 3, 10 );

		$before = time();
		Cleanup::cleanup_logs();

		$next = wp_next_scheduled( 'swpl_cleanup_logs' );
		$this->assertNotFalse( $next );
		$this->assertGreaterThanOrEqual( $before + 5 * MINUTE_IN_SECONDS, $next );
		$this->assertLessThanOrEqual( time() + 5 * MINUTE_IN_SECONDS, $next );
		$this->assertFalse( wp_next_scheduled( 'swpl_cleanup_requests' ) );
	}

	public function test_a_complete_purge_schedules_nothing_extra() {
		$this->insert_logs( 3, 10 );

		Cleanup::cleanup_logs();

		$this->assertFalse( wp_next_scheduled( 'swpl_cleanup_logs' ) );
	}

	public function test_follow_up_is_not_added_when_a_run_is_already_due_soon() {
		$this->batch_size( 1 );
		$this->ticking_clock( 100 );
		$this->insert_logs( 3, 10 );
		wp_schedule_event( time() + MINUTE_IN_SECONDS, 'hourly', 'swpl_cleanup_logs' );

		// Core also refuses an identical event within 10 minutes, so counting
		// events alone cannot tell whether the purge tried to add one.
		$attempts = $this->spy_on_scheduling( 'swpl_cleanup_logs' );
		Cleanup::cleanup_logs();

		$this->assertSame( 0, $attempts->count );
		$this->assertSame( 1, $this->count_events( 'swpl_cleanup_logs' ) );
	}

	public function test_repeated_paused_runs_keep_a_single_follow_up() {
		$this->batch_size( 1 );
		$this->ticking_clock( 100 );
		$this->insert_logs( 5, 10 );

		Cleanup::cleanup_logs();
		$attempts = $this->spy_on_scheduling( 'swpl_cleanup_logs' );
		Cleanup::cleanup_logs();

		$this->assertSame( 0, $attempts->count );
		$this->assertSame( 1, $this->count_events( 'swpl_cleanup_logs' ) );
	}

	public function test_a_zero_time_budget_still_runs_one_batch() {
		$this->batch_size( 1 );
		add_filter( 'swpl_purge_time_budget', '__return_zero' );
		$this->insert_logs( 3, 10 );

		$result = Cleanup::cleanup_logs();

		$this->assertSame( 1, $result['batches'] );
		$this->assertSame( 1, $result['deleted'] );
		$this->assertFalse( $result['complete'] );
	}

	/**
	 * Count attempts to schedule $hook, whether or not core accepts them.
	 */
	private function spy_on_scheduling( $hook ) {
		$attempts = (object) [ 'count' => 0 ];
		add_filter(
			'pre_schedule_event',
			function ( $pre, $event ) use ( $attempts, $hook ) {
				if ( $hook === $event->hook ) {
					++$attempts->count;
				}
				return $pre;
			},
			10,
			2
		);
		return $attempts;
	}

	/**
	 * Number of scheduled events for $hook.
	 */
	private function count_events( $hook ) {
		$events = 0;
		foreach ( _get_cron_array() as $hooks ) {
			if ( isset( $hooks[ $hook ] ) ) {
				$events += count( $hooks[ $hook ] );
			}
		}
		return $events;
	}

	public function test_an_interrupted_purge_is_logged() {
		$this->batch_size( 1 );
		$this->ticking_clock( 100 );
		$this->insert_logs( 3, 10 );

		$logged = [];
		$spy    = function ( $source, $message, $context, $level ) use ( &$logged ) {
			$logged[] = compact( 'source', 'message', 'context', 'level' );
		};
		add_action( 'swpl_log', $spy, 5, 4 );
		Cleanup::cleanup_logs();
		remove_action( 'swpl_log', $spy, 5 );

		$this->assertCount( 1, $logged );
		$this->assertSame( 'WP Logs', $logged[0]['source'] );
		$this->assertSame( 1, $logged[0]['context']['deleted'] );
		$this->assertSame( 'logs', $logged[0]['context']['table'] );
	}

	public function test_a_complete_purge_is_not_logged() {
		$this->insert_logs( 3, 10 );

		$logged = 0;
		$spy    = function () use ( &$logged ) {
			$logged++;
		};
		add_action( 'swpl_log', $spy, 5 );
		Cleanup::cleanup_logs();
		remove_action( 'swpl_log', $spy, 5 );

		$this->assertSame( 0, $logged );
	}

	public function test_purge_result_is_passed_to_an_action() {
		$this->insert_requests( 2, 10 );

		$seen = [];
		add_action(
			'swpl_purged',
			function ( $type, $result ) use ( &$seen ) {
				$seen[] = [ $type, $result ];
			},
			10,
			2
		);
		Cleanup::cleanup_requests();

		$this->assertCount( 1, $seen );
		$this->assertSame( 'requests', $seen[0][0] );
		$this->assertSame( 2, $seen[0][1]['deleted'] );
	}
}
