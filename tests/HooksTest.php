<?php
/**
 * Tests for storing logs through the swpl_log action.
 *
 * @package Shazzad\WpLogs
 */

use Shazzad\WpLogs\DbAdapter;
use Shazzad\WpLogs\Hooks;

/**
 * The message is a Mustache template filled from the context. These tests pin
 * what gets stored, and that a template which cannot be rendered never takes
 * the request down or loses the log entry.
 */
class HooksTest extends WP_UnitTestCase {

	/**
	 * File error_log() writes to during a test.
	 *
	 * @var string
	 */
	private $error_log_file;

	/**
	 * The error_log ini value before the test.
	 *
	 * @var string|false
	 */
	private $previous_error_log;

	public function set_up() {
		parent::set_up();

		global $wpdb;
		// DELETE, not TRUNCATE: TRUNCATE commits the test transaction.
		$wpdb->query( 'DELETE FROM ' . DbAdapter::prefix_table( 'logs' ) );

		$this->error_log_file     = wp_tempnam( 'swpl-error-log' );
		$this->previous_error_log = ini_set( 'error_log', $this->error_log_file );
	}

	public function tear_down() {
		ini_set( 'error_log', (string) $this->previous_error_log );
		if ( file_exists( $this->error_log_file ) ) {
			unlink( $this->error_log_file );
		}

		parent::tear_down();
	}

	/**
	 * The most recently stored log row.
	 *
	 * @return array|null
	 */
	private function last_log() {
		global $wpdb;

		return $wpdb->get_row(
			'SELECT message, message_raw, level FROM ' . DbAdapter::prefix_table( 'logs' ) . ' ORDER BY id DESC LIMIT 1',
			ARRAY_A
		);
	}

	/**
	 * Number of stored log rows.
	 *
	 * @return int
	 */
	private function log_count() {
		global $wpdb;

		return (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . DbAdapter::prefix_table( 'logs' ) );
	}

	public function test_placeholders_are_filled_from_the_context() {
		do_action(
			'swpl_log',
			'test',
			'Order {{order.id}} for {{name}}: {{#items}}[{{.}}]{{/items}}',
			[
				'order' => [ 'id' => 5 ],
				'name'  => 'Ann',
				'items' => [ 1, 2 ],
			]
		);

		$row = $this->last_log();
		$this->assertSame( 'Order 5 for Ann: [1][2]', $row['message'] );
		$this->assertSame( 'Order {{order.id}} for {{name}}: {{#items}}[{{.}}]{{/items}}', $row['message_raw'] );
	}

	public function test_double_braces_escape_html_and_triple_braces_do_not() {
		do_action( 'swpl_log', 'test', '{{v}} | {{{v}}}', [ 'v' => 'A & B "q" <b>' ] );

		$this->assertSame( 'A &amp; B &quot;q&quot; &lt;b&gt; | A & B "q" <b>', $this->last_log()['message'] );
	}

	public function test_message_without_context_is_stored_as_written() {
		do_action( 'swpl_log', 'test', 'Plain {{not_a_placeholder}} message' );

		$this->assertSame( 'Plain {{not_a_placeholder}} message', $this->last_log()['message'] );
	}

	public function test_placeholder_pointing_at_an_array_saves_the_unrendered_message() {
		do_action( 'swpl_log', 'test', 'Response: {{response}}', [ 'response' => [ 'code' => 500 ] ], 'error' );

		$this->assertSame( 1, $this->log_count() );
		$row = $this->last_log();
		$this->assertSame( 'Response: {{response}}', $row['message'] );
		$this->assertSame( 'error', $row['level'] );
		$this->assertStringContainsString( 'could not render the log message template', file_get_contents( $this->error_log_file ) );
	}

	public function test_unclosed_section_saves_the_unrendered_message() {
		do_action( 'swpl_log', 'test', 'Body was {{#items}} without a close', [ 'id' => 1 ] );

		$this->assertSame( 1, $this->log_count() );
		$this->assertSame( 'Body was {{#items}} without a close', $this->last_log()['message'] );
		$this->assertStringContainsString( 'could not render the log message template', file_get_contents( $this->error_log_file ) );
	}

	/**
	 * The bundled Mustache 3 ships aliases for the old Mustache_* class names.
	 * Other plugins bundle Mustache 2 under those names, so WP Logs' autoloader
	 * must never answer for them, or it would swap their engine for ours.
	 */
	public function test_legacy_mustache_class_names_are_not_served() {
		do_action( 'swpl_log', 'test', 'Hello {{n}}', [ 'n' => 7 ] );

		$this->assertSame( 'Hello 7', $this->last_log()['message'] );
		$this->assertFalse( class_exists( 'Mustache_Engine' ) );
		$this->assertFalse( interface_exists( 'Mustache_Exception' ) );
	}

	public function test_a_non_string_source_never_takes_the_request_down() {
		do_action( 'swpl_log', [ 'not', 'a', 'string' ], 'Message' );

		$this->addToAssertionCount( 1 );
	}

	public function test_a_non_string_message_is_not_rendered() {
		do_action( 'swpl_log', 'test', 42, [ 'n' => 1 ] );

		$this->assertSame( '42', (string) $this->last_log()['message'] );
	}

	public function test_render_message_returns_a_string() {
		$this->assertSame( 'Hello 7', Hooks::render_message( 'Hello {{n}}', [ 'n' => 7 ] ) );
	}
}
