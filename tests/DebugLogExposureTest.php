<?php
/**
 * Tests for the debug log exposure check and its notice.
 *
 * @package Shazzad\WpLogs
 */

use Shazzad\WpLogs\Admin\DebugLogExposureNotice;
use Shazzad\WpLogs\DebugLogExposure;
use Shazzad\WpLogs\WpDebugLog;

/**
 * HTTP is stubbed with pre_http_request; nothing leaves the test process.
 */
class DebugLogExposureTest extends WP_UnitTestCase {
	/**
	 * Requests seen by the stub: [ url, method ].
	 *
	 * @var array
	 */
	private $requests = [];

	/**
	 * Queue of responses the stub returns, in order. Each is an int status
	 * code or a WP_Error.
	 *
	 * @var array
	 */
	private $responses = [];

	/**
	 * Log file created for the test, removed afterwards.
	 *
	 * @var string
	 */
	private $log_file;

	/**
	 * Args of the last request the stub saw.
	 *
	 * @var array
	 */
	private $last_args = [];

	public function set_up() {
		parent::set_up();

		$this->requests  = [];
		$this->responses = [];

		$this->log_file = WpDebugLog::get_log_file();
		file_put_contents( $this->log_file, "[25-Sep-2026 00:00:00 UTC] PHP Notice: test\n" );

		add_filter( 'pre_http_request', [ $this, 'stub_http' ], 10, 3 );
	}

	public function tear_down() {
		remove_filter( 'pre_http_request', [ $this, 'stub_http' ], 10 );
		unset( $_REQUEST['_wpnonce'] );
		if ( file_exists( $this->log_file ) ) {
			unlink( $this->log_file );
		}
		parent::tear_down();
	}

	public function stub_http( $pre, $args, $url ) {
		$this->requests[] = [ $url, $args['method'] ];
		$this->last_args  = $args;

		$next = array_shift( $this->responses );
		if ( null === $next ) {
			$this->fail( 'Unexpected HTTP request to ' . $url );
		}
		if ( is_wp_error( $next ) ) {
			return $next;
		}

		$headers = [];
		if ( is_array( $next ) ) {
			$headers = $next['headers'];
			$next    = $next['code'];
		}

		return [
			// Same header container core's HTTP API returns: case-insensitive keys.
			'headers'  => new WpOrg\Requests\Utility\CaseInsensitiveDictionary( $headers ),
			'body'     => '',
			'response' => [
				'code'    => $next,
				'message' => get_status_header_desc( $next ),
			],
			'cookies'  => [],
			'filename' => null,
		];
	}

	private function expected_url() {
		return content_url( '/debug.log' );
	}

	public function test_log_path_defaults_to_wp_content_dir() {
		$this->assertSame( WP_CONTENT_DIR . '/debug.log', WpDebugLog::get_log_file() );
	}

	public function test_path_under_wp_content_maps_to_content_url() {
		$this->assertSame( $this->expected_url(), DebugLogExposure::path_to_url( WP_CONTENT_DIR . '/debug.log' ) );
	}

	public function test_path_under_abspath_maps_to_site_url() {
		$this->assertSame( site_url( '/logs/debug.log' ), DebugLogExposure::path_to_url( ABSPATH . 'logs/debug.log' ) );
	}

	public function test_path_outside_web_root_has_no_url() {
		$this->assertSame( '', DebugLogExposure::path_to_url( '/var/log/php/debug.log' ) );
	}

	public function test_200_with_404_control_is_answered() {
		$this->responses = [ 200, 404 ];

		$result = DebugLogExposure::get_result();

		$this->assertSame( DebugLogExposure::STATUS_ANSWERED, $result['status'] );
		$this->assertSame( 200, $result['code'] );
		$this->assertSame( $this->expected_url(), $result['url'] );
		$this->assertCount( 2, $this->requests );
		$this->assertSame( [ $this->expected_url(), 'HEAD' ], $this->requests[0] );
		$this->assertSame( 'HEAD', $this->requests[1][1], 'The control request is a HEAD too.' );
		$this->assertStringContainsString( 'swpl-probe-', $this->requests[1][0] );
	}

	public function test_200_for_a_missing_file_too_is_unknown_not_answered() {
		$this->responses = [ 200, 200 ];

		$result = DebugLogExposure::get_result();

		$this->assertSame( DebugLogExposure::STATUS_UNKNOWN, $result['status'] );
		$this->assertStringContainsString( 'does not exist', $result['reason'] );
	}

	/**
	 * A control answer that says "no such file" is what makes a 200 for the
	 * log mean something.
	 *
	 * @dataProvider not_a_catch_all_codes
	 */
	public function test_200_with_not_found_style_control_is_answered( $control_code ) {
		$this->responses = [ 200, $control_code ];

		$result = DebugLogExposure::get_result();

		$this->assertSame( DebugLogExposure::STATUS_ANSWERED, $result['status'] );
		$this->assertSame( 200, $result['code'] );
	}

	public function not_a_catch_all_codes() {
		return [
			'404' => [ 404 ],
			'410' => [ 410 ],
			'403' => [ 403 ],
		];
	}

	/**
	 * Any other control answer proves nothing about how the server treats
	 * files that do not exist, so the 200 for the log proves nothing either.
	 *
	 * @dataProvider inconclusive_control_codes
	 */
	public function test_200_with_inconclusive_control_status_is_unknown( $control_code ) {
		$this->responses = [ 200, $control_code ];

		$result = DebugLogExposure::get_result();

		$this->assertSame( DebugLogExposure::STATUS_UNKNOWN, $result['status'] );
		$this->assertSame( 200, $result['code'] );
		$this->assertStringContainsString( sprintf( 'HTTP %d', $control_code ), $result['reason'] );
	}

	public function inconclusive_control_codes() {
		return [
			'500' => [ 500 ],
			'502' => [ 502 ],
			'503' => [ 503 ],
			'429' => [ 429 ],
			'401' => [ 401 ],
			'302' => [ 302 ],
		];
	}

	public function test_200_with_failed_control_request_is_unknown() {
		$this->responses = [ 200, new WP_Error( 'http_request_failed', 'cURL error 28: Operation timed out' ) ];

		$result = DebugLogExposure::get_result();

		$this->assertSame( DebugLogExposure::STATUS_UNKNOWN, $result['status'] );
		$this->assertSame( 200, $result['code'] );
		$this->assertStringContainsString( 'timed out', $result['reason'] );
	}

	public function test_200_as_html_is_unknown_without_a_control_request() {
		$this->responses = [
			[
				'code'    => 200,
				'headers' => [ 'Content-Type' => 'text/html; charset=UTF-8' ],
			],
		];

		$result = DebugLogExposure::get_result();

		$this->assertSame( DebugLogExposure::STATUS_UNKNOWN, $result['status'] );
		$this->assertSame( 200, $result['code'] );
		$this->assertStringContainsString( 'text/html', $result['reason'] );
		$this->assertCount( 1, $this->requests, 'An HTML answer settles it; no control request.' );
	}

	public function test_200_as_html_in_upper_case_is_unknown() {
		$this->responses = [
			[
				'code'    => 200,
				'headers' => [ 'content-type' => 'TEXT/HTML' ],
			],
		];

		$this->assertSame( DebugLogExposure::STATUS_UNKNOWN, DebugLogExposure::get_result()['status'] );
	}

	/**
	 * @dataProvider log_file_content_types
	 */
	public function test_200_as_a_log_file_type_is_answered( $content_type ) {
		$this->responses = [
			[
				'code'    => 200,
				'headers' => [ 'Content-Type' => $content_type ],
			],
			404,
		];

		$result = DebugLogExposure::get_result();

		$this->assertSame( DebugLogExposure::STATUS_ANSWERED, $result['status'] );
		$this->assertCount( 2, $this->requests );
	}

	public function log_file_content_types() {
		return [
			'text/plain'               => [ 'text/plain' ],
			'text/plain with charset'  => [ 'text/plain; charset=UTF-8' ],
			'application/octet-stream' => [ 'application/octet-stream' ],
		];
	}

	public function test_403_is_refused() {
		$this->responses = [ 403 ];

		$result = DebugLogExposure::get_result();

		$this->assertSame( DebugLogExposure::STATUS_REFUSED, $result['status'] );
		$this->assertSame( 403, $result['code'] );
		$this->assertCount( 1, $this->requests, 'No control request after a refusal.' );
	}

	public function test_404_is_refused() {
		$this->responses = [ 404 ];

		$this->assertSame( DebugLogExposure::STATUS_REFUSED, DebugLogExposure::get_result()['status'] );
	}

	public function test_wp_error_is_unknown_never_safe() {
		$this->responses = [ new WP_Error( 'http_request_failed', 'cURL error 28: Operation timed out' ) ];

		$result = DebugLogExposure::get_result();

		$this->assertSame( DebugLogExposure::STATUS_UNKNOWN, $result['status'] );
		$this->assertNull( $result['code'] );
		$this->assertStringContainsString( 'timed out', $result['reason'] );
	}

	public function test_other_status_is_unknown() {
		$this->responses = [ 500 ];

		$result = DebugLogExposure::get_result();

		$this->assertSame( DebugLogExposure::STATUS_UNKNOWN, $result['status'] );
		$this->assertSame( 500, $result['code'] );
	}

	public function test_outside_web_root_makes_no_request() {
		add_filter( 'swpl_debug_log_exposure_url', '__return_empty_string' );

		$result = DebugLogExposure::get_result();

		$this->assertSame( DebugLogExposure::STATUS_OUTSIDE_WEB_ROOT, $result['status'] );
		$this->assertCount( 0, $this->requests );
	}

	public function test_missing_log_file_makes_no_request() {
		unlink( $this->log_file );

		$this->assertSame( DebugLogExposure::STATUS_NO_FILE, DebugLogExposure::get_result()['status'] );
		$this->assertCount( 0, $this->requests );
	}

	public function test_result_is_cached_for_a_day() {
		$this->responses = [ 403 ];

		$first  = DebugLogExposure::get_result();
		$second = DebugLogExposure::get_result();

		$this->assertCount( 1, $this->requests, 'Second call must come from the transient.' );
		$this->assertFalse( $first['cached'] );
		$this->assertTrue( $second['cached'] );
		$this->assertSame( DebugLogExposure::STATUS_REFUSED, $second['status'] );

		$key     = DebugLogExposure::cache_key( $this->expected_url() );
		$timeout = (int) get_option( '_transient_timeout_' . $key );
		$this->assertSame( 'swpl_dle_' . md5( $this->expected_url() ), $key );
		$this->assertGreaterThanOrEqual( time() + DAY_IN_SECONDS - 5, $timeout );
	}

	public function test_force_skips_the_cache() {
		$this->responses = [ 403, 200, 404 ];

		DebugLogExposure::get_result();
		$forced = DebugLogExposure::get_result( true );

		$this->assertCount( 3, $this->requests );
		$this->assertSame( DebugLogExposure::STATUS_ANSWERED, $forced['status'] );
	}

	public function test_path_change_busts_the_old_cache() {
		$this->responses = [ 403, 200, 404 ];

		DebugLogExposure::get_result();
		$old_key = DebugLogExposure::cache_key( $this->expected_url() );
		$this->assertIsArray( get_transient( $old_key ) );

		$moved = content_url( '/logs/debug.log' );
		add_filter(
			'swpl_debug_log_exposure_url',
			function () use ( $moved ) {
				return $moved;
			}
		);

		$result = DebugLogExposure::get_result();

		$this->assertFalse( get_transient( $old_key ), 'Old URL result dropped.' );
		$this->assertSame( $moved, $result['url'] );
		$this->assertSame( DebugLogExposure::STATUS_ANSWERED, $result['status'] );
		$this->assertCount( 3, $this->requests );
	}

	public function test_filter_disables_the_whole_check() {
		add_filter( 'swpl_debug_log_exposure_check', '__return_false' );

		$this->assertNull( DebugLogExposure::get_result() );
		$this->assertCount( 0, $this->requests );
	}

	public function test_refused_clears_a_dismissal_so_a_later_answer_shows_again() {
		DebugLogExposure::dismiss( $this->expected_url() );
		$this->assertTrue( DebugLogExposure::is_dismissed( $this->expected_url() ) );

		$this->responses = [ 403 ];
		DebugLogExposure::get_result( true );

		$this->assertFalse( DebugLogExposure::is_dismissed( $this->expected_url() ) );
	}

	public function test_unknown_keeps_a_dismissal() {
		DebugLogExposure::dismiss( $this->expected_url() );

		$this->responses = [ new WP_Error( 'http_request_failed', 'boom' ) ];
		DebugLogExposure::get_result( true );

		$this->assertTrue( DebugLogExposure::is_dismissed( $this->expected_url() ) );
	}

	public function test_notice_shows_on_logs_screen_when_answered() {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
		set_current_screen( DebugLogExposureNotice::SCREEN_ID );
		$this->responses = [ 200, 404 ];

		ob_start();
		DebugLogExposureNotice::render();
		$html = ob_get_clean();

		$this->assertStringContainsString( 'wp-content/debug.log</code> answered a HEAD request from this server with HTTP 200', $html );
		$this->assertStringContainsString( 'Anyone who knows the URL may be able to read it.', $html );
		$this->assertStringContainsString( 'Require all denied', $html );
		$this->assertStringContainsString( 'location = /wp-content/debug.log', $html );
		$this->assertStringContainsString( DebugLogExposureNotice::DISMISS_ACTION, $html );
	}

	public function test_notice_hidden_on_other_screens() {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
		set_current_screen( 'dashboard' );

		ob_start();
		DebugLogExposureNotice::render();
		$this->assertSame( '', ob_get_clean() );
		$this->assertCount( 0, $this->requests, 'No request off the Logs screen.' );
	}

	public function test_notice_hidden_for_non_admins() {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'editor' ] ) );
		set_current_screen( DebugLogExposureNotice::SCREEN_ID );

		ob_start();
		DebugLogExposureNotice::render();
		$this->assertSame( '', ob_get_clean() );
		$this->assertCount( 0, $this->requests );
	}

	public function test_notice_hidden_once_dismissed() {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
		set_current_screen( DebugLogExposureNotice::SCREEN_ID );
		$this->responses = [ 200, 404 ];
		DebugLogExposure::dismiss( $this->expected_url() );

		ob_start();
		DebugLogExposureNotice::render();
		$this->assertSame( '', ob_get_clean() );
	}

	public function test_notice_hidden_when_unknown() {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
		set_current_screen( DebugLogExposureNotice::SCREEN_ID );
		$this->responses = [ new WP_Error( 'http_request_failed', 'blocked' ) ];

		ob_start();
		DebugLogExposureNotice::render();
		$this->assertSame( '', ob_get_clean() );
	}

	public function test_unknown_result_is_cached_too() {
		$this->responses = [ new WP_Error( 'http_request_failed', 'cURL error 28: Operation timed out' ) ];

		$first  = DebugLogExposure::get_result();
		$second = DebugLogExposure::get_result();

		$this->assertSame( DebugLogExposure::STATUS_UNKNOWN, $first['status'] );
		$this->assertSame( DebugLogExposure::STATUS_UNKNOWN, $second['status'] );
		$this->assertTrue( $second['cached'], 'A failed probe must not re-run on every Logs screen load.' );
		$this->assertCount( 1, $this->requests );
	}

	public function test_request_is_bounded() {
		$this->responses = [ 403 ];

		DebugLogExposure::get_result();

		$this->assertSame( 5, $this->last_args['timeout'] );
		$this->assertSame( 3, $this->last_args['redirection'] );
	}

	/**
	 * Make wp_redirect() throw, so a handler's exit is never reached.
	 */
	private function catch_redirect() {
		add_filter(
			'wp_redirect',
			function ( $location ) {
				throw new RuntimeException( 'redirect:' . $location );
			}
		);
	}

	private function run_handler( $callback ) {
		try {
			call_user_func( $callback );
		} catch ( RuntimeException $e ) {
			return $e->getMessage();
		}
		$this->fail( 'Handler did not redirect.' );
	}

	public function test_dismiss_handler_stores_dismissal_and_redirects_to_logs_screen() {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
		$_REQUEST['_wpnonce'] = wp_create_nonce( DebugLogExposureNotice::DISMISS_ACTION );
		$this->responses      = [ 200, 404 ];
		$this->catch_redirect();

		$location = $this->run_handler( [ DebugLogExposureNotice::class, 'handle_dismiss' ] );

		$this->assertSame( 'redirect:' . admin_url( 'admin.php?page=shazzad-wp-logs' ), $location );
		$this->assertTrue( DebugLogExposure::is_dismissed( $this->expected_url() ) );
	}

	public function test_recheck_handler_skips_the_cache() {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
		$this->responses = [ 200, 404, 403 ];
		DebugLogExposure::get_result();

		$_REQUEST['_wpnonce'] = wp_create_nonce( DebugLogExposureNotice::RECHECK_ACTION );
		$this->catch_redirect();

		$location = $this->run_handler( [ DebugLogExposureNotice::class, 'handle_recheck' ] );

		$this->assertSame( 'redirect:' . admin_url( 'admin.php?page=shazzad-wp-logs' ), $location );
		$this->assertCount( 3, $this->requests );
		$this->assertSame( DebugLogExposure::STATUS_REFUSED, DebugLogExposure::get_result()['status'] );
	}

	public function test_handlers_refuse_non_admins() {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'editor' ] ) );
		$_REQUEST['_wpnonce'] = wp_create_nonce( DebugLogExposureNotice::DISMISS_ACTION );

		try {
			DebugLogExposureNotice::handle_dismiss();
			$this->fail( 'A non-admin reached the dismiss handler.' );
		} catch ( WPDieException $e ) {
			$this->assertCount( 0, $this->requests );
			$this->assertFalse( get_option( DebugLogExposure::DISMISSED_OPTION ) );
		}
	}

	public function test_handlers_refuse_a_missing_nonce() {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		try {
			DebugLogExposureNotice::handle_recheck();
			$this->fail( 'The recheck handler ran without a nonce.' );
		} catch ( WPDieException $e ) {
			$this->assertCount( 0, $this->requests );
		}
	}
}
