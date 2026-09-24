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
		if ( file_exists( $this->log_file ) ) {
			unlink( $this->log_file );
		}
		parent::tear_down();
	}

	public function stub_http( $pre, $args, $url ) {
		$this->requests[] = [ $url, $args['method'] ];

		$next = array_shift( $this->responses );
		if ( null === $next ) {
			$this->fail( 'Unexpected HTTP request to ' . $url );
		}
		if ( is_wp_error( $next ) ) {
			return $next;
		}

		return [
			'headers'  => [],
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
}
