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
 * Purges logs and requests older than their retention period. The purge
 * deletes in small batches, oldest first, and stops once a time budget is
 * spent, so a site with a large backlog clears it over several cron runs
 * instead of locking the table in one long DELETE.
 */
class Cleanup {

	/**
	 * Rows deleted per query, unless filtered by `swpl_purge_batch_size`.
	 */
	const DEFAULT_BATCH_SIZE = 5000;

	/**
	 * Seconds one run may spend deleting, unless filtered by `swpl_purge_time_budget`.
	 */
	const DEFAULT_TIME_BUDGET = 20;

	/**
	 * Delay before a follow-up run when a purge stops with rows left.
	 */
	const FOLLOW_UP_DELAY = 5 * MINUTE_IN_SECONDS;

	/**
	 * Cron hook and retention option for each table.
	 */
	const TABLES = [
		'logs'     => [
			'hook'   => 'swpl_cleanup_logs',
			'option' => 'swpl_log_retention_days',
		],
		'requests' => [
			'hook'   => 'swpl_cleanup_requests',
			'option' => 'swpl_request_retention_days',
		],
	];

	/**
	 * Clock override for tests; null means microtime( true ).
	 *
	 * @var callable|null
	 */
	private static $clock = null;

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
	 * Purge logs older than the log retention period.
	 *
	 * @return array See purge().
	 */
	public static function cleanup_logs() {
		return self::purge( 'logs' );
	}

	/**
	 * Purge requests older than the request retention period.
	 *
	 * @return array See purge().
	 */
	public static function cleanup_requests() {
		return self::purge( 'requests' );
	}

	/**
	 * Delete expired rows from one table in batches, oldest first.
	 *
	 * Each batch is its own statement, so locks are released between batches.
	 * The run stops when a batch comes back short (nothing left), when a query
	 * fails, or when the time budget is spent. In the last case a follow-up run
	 * is scheduled, and the next run picks up where this one stopped.
	 *
	 * @param string $type 'logs' or 'requests'.
	 * @return array {
	 *     @type int  $deleted  Rows deleted by this run.
	 *     @type int  $batches  DELETE statements run.
	 *     @type bool $complete True when no expired rows are left.
	 * }
	 */
	public static function purge( $type ) {
		global $wpdb;

		$table      = DbAdapter::prefix_table( $type );
		$cutoff     = gmdate( 'Y-m-d H:i:s', time() - self::get_retention_days( $type ) * DAY_IN_SECONDS );
		$batch_size = self::get_batch_size( $type );
		$budget     = self::get_time_budget( $type );
		$started    = self::now();

		$result = [
			'deleted'  => 0,
			'batches'  => 0,
			'complete' => false,
		];

		while ( true ) {
			// date_created is stored in GMT; ordering by the primary key walks
			// the oldest rows first without needing an index on date_created.
			$deleted = $wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$table} WHERE date_created < %s ORDER BY id ASC LIMIT %d",
					$cutoff,
					$batch_size
				)
			);

			if ( false === $deleted ) {
				do_action(
					'swpl_log',
					'WP Logs',
					'Retention purge of the {{table}} table failed: {{error}}',
					[
						'table' => $type,
						'error' => $wpdb->last_error,
					],
					'error'
				);
				break;
			}

			++$result['batches'];
			$result['deleted'] += (int) $deleted;

			if ( $deleted < $batch_size ) {
				$result['complete'] = true;
				break;
			}

			if ( self::now() - $started >= $budget ) {
				break;
			}
		}

		if ( ! $result['complete'] && false !== $deleted ) {
			self::schedule_follow_up( $type );

			do_action(
				'swpl_log',
				'WP Logs',
				'Retention purge of the {{table}} table paused after {{deleted}} rows in {{batches}} batches; the next run continues it.',
				[
					'table'   => $type,
					'deleted' => $result['deleted'],
					'batches' => $result['batches'],
				],
				'info'
			);
		}

		/**
		 * Fires after a retention purge run.
		 *
		 * @param string $type   'logs' or 'requests'.
		 * @param array  $result deleted, batches, complete.
		 */
		do_action( 'swpl_purged', $type, $result );

		return $result;
	}

	/**
	 * Rows deleted per statement.
	 *
	 * @param string $type 'logs' or 'requests'.
	 * @return int
	 */
	public static function get_batch_size( $type ) {
		/**
		 * Filters how many rows one purge statement deletes.
		 *
		 * @param int    $size Default 5000.
		 * @param string $type 'logs' or 'requests'.
		 */
		$size = (int) apply_filters( 'swpl_purge_batch_size', self::DEFAULT_BATCH_SIZE, $type );

		return $size > 0 ? $size : self::DEFAULT_BATCH_SIZE;
	}

	/**
	 * Seconds one purge run may spend deleting.
	 *
	 * @param string $type 'logs' or 'requests'.
	 * @return float
	 */
	public static function get_time_budget( $type ) {
		/**
		 * Filters how long one purge run keeps deleting before it stops and
		 * leaves the rest to the next run. At least one batch always runs.
		 *
		 * @param int    $seconds Default 20.
		 * @param string $type    'logs' or 'requests'.
		 */
		$budget = (float) apply_filters( 'swpl_purge_time_budget', self::DEFAULT_TIME_BUDGET, $type );

		return $budget >= 0 ? $budget : (float) self::DEFAULT_TIME_BUDGET;
	}

	/**
	 * Retention period for a table, in days.
	 *
	 * @param string $type 'logs' or 'requests'.
	 * @return int
	 */
	public static function get_retention_days( $type ) {
		$days = (int) get_option( self::TABLES[ $type ]['option'], 7 );

		return $days < 1 ? 7 : $days;
	}

	/**
	 * Replace the clock the time budget is measured with. Tests only.
	 *
	 * @param callable|null $clock Returns seconds as a float; null restores microtime( true ).
	 * @return void
	 */
	public static function set_clock( $clock ) {
		self::$clock = $clock;
	}

	/**
	 * Current time in seconds.
	 *
	 * @return float
	 */
	private static function now() {
		return self::$clock ? (float) call_user_func( self::$clock ) : microtime( true );
	}

	/**
	 * Run the purge again soon, unless a run is already due within that time.
	 *
	 * @param string $type 'logs' or 'requests'.
	 * @return void
	 */
	private static function schedule_follow_up( $type ) {
		$hook = self::TABLES[ $type ]['hook'];
		$when = time() + self::FOLLOW_UP_DELAY;
		$next = wp_next_scheduled( $hook );

		if ( false === $next || $next > $when ) {
			wp_schedule_single_event( $when, $hook );
		}
	}
}
