<?php
/**
 * Tests for the debug log viewer's script registration.
 *
 * @package Shazzad\WpLogs
 */

use Shazzad\WpLogs\WpDebugLog;

/**
 * The viewer script is written against jQuery, so it must declare it.
 */
class WpDebugLogScriptTest extends WP_UnitTestCase {
	/**
	 * Leave no registered or enqueued handle behind for the next test.
	 */
	public function tear_down() {
		wp_dequeue_script( 'swpl-wp-debug-log' );
		wp_deregister_script( 'swpl-wp-debug-log' );
		wp_dequeue_style( 'swpl-wp-debug-log' );
		wp_deregister_style( 'swpl-wp-debug-log' );

		parent::tear_down();
	}

	/**
	 * Front-end themes that load no jQuery of their own (Twenty Twenty-Five)
	 * got "jQuery is not defined" on every page for administrators, because
	 * the script declared no dependencies (#87).
	 */
	public function test_viewer_script_depends_on_jquery() {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		WpDebugLog::enqueue_scripts();

		$script = wp_scripts()->query( 'swpl-wp-debug-log', 'registered' );

		$this->assertNotFalse( $script, 'The viewer script was not registered.' );
		$this->assertContains( 'jquery', $script->deps );
		$this->assertTrue( wp_script_is( 'swpl-wp-debug-log', 'enqueued' ) );
	}

	/**
	 * Visitors and non-administrators get neither the script nor jQuery
	 * pulled in on its behalf.
	 */
	public function test_viewer_script_not_enqueued_for_non_administrators() {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'editor' ] ) );

		WpDebugLog::enqueue_scripts();

		$this->assertFalse( wp_script_is( 'swpl-wp-debug-log', 'registered' ) );
	}
}
