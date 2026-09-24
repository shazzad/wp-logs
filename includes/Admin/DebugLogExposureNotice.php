<?php
/**
 * Admin notice for a debug log that answers HTTP requests.
 *
 * @package Shazzad\WpLogs
 */

namespace Shazzad\WpLogs\Admin;

use Shazzad\WpLogs\DebugLogExposure;

/**
 * Shows the result of the debug log exposure check on the plugin's own screen.
 *
 * Only an "answered" result produces a notice. "unknown" is kept out of the
 * UI on purpose: a notice every time a loopback request is blocked would be
 * noise, and noise is what teaches administrators to ignore the real one.
 *
 * @since n.e.x.t
 */
class DebugLogExposureNotice {
	/**
	 * Screen id of the Logs page (top-level menu, slug shazzad-wp-logs).
	 */
	const SCREEN_ID = 'toplevel_page_shazzad-wp-logs';

	const DISMISS_ACTION = 'swpl_dismiss_debug_log_exposure';
	const RECHECK_ACTION = 'swpl_recheck_debug_log_exposure';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function setup() {
		add_action( 'admin_notices', [ __CLASS__, 'render' ] );
		add_action( 'admin_post_' . self::DISMISS_ACTION, [ __CLASS__, 'handle_dismiss' ] );
		add_action( 'admin_post_' . self::RECHECK_ACTION, [ __CLASS__, 'handle_recheck' ] );
	}

	/**
	 * Print the notice on the Logs screen when the log answered.
	 *
	 * @return void
	 */
	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || self::SCREEN_ID !== $screen->id ) {
			return;
		}

		$result = DebugLogExposure::get_result();
		if ( ! $result || DebugLogExposure::STATUS_ANSWERED !== $result['status'] ) {
			return;
		}

		if ( DebugLogExposure::is_dismissed( $result['url'] ) ) {
			return;
		}

		echo self::get_html( $result ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in get_html().
	}

	/**
	 * Build the notice markup.
	 *
	 * @param array $result Exposure result with status "answered".
	 * @return string
	 */
	public static function get_html( $result ) {
		$display  = self::display_path( $result['path'] );
		$url_path = wp_parse_url( $result['url'], PHP_URL_PATH );
		$basename = wp_basename( $result['path'] );

		$apache = sprintf( "<Files \"%s\">\n    Require all denied\n</Files>", $basename );
		$nginx  = sprintf( "location = %s {\n    deny all;\n}", $url_path ? $url_path : '/wp-content/debug.log' );

		$dismiss_url = wp_nonce_url( admin_url( 'admin-post.php?action=' . self::DISMISS_ACTION ), self::DISMISS_ACTION );
		$recheck_url = wp_nonce_url( admin_url( 'admin-post.php?action=' . self::RECHECK_ACTION ), self::RECHECK_ACTION );

		ob_start();
		?>
		<div class="notice notice-warning swpl-debug-log-exposure">
			<p>
				<strong><?php esc_html_e( 'Debug log:', 'swpl' ); ?></strong>
				<?php
				printf(
					/* translators: 1: debug log path, 2: HTTP status code. */
					esc_html__( '%1$s answered a HEAD request from this server with HTTP %2$d. Anyone who knows the URL may be able to read it.', 'swpl' ),
					'<code>' . esc_html( $display ) . '</code>',
					(int) $result['code']
				);
				?>
			</p>
			<p>
				<?php
				printf(
					/* translators: 1: URL that was requested, 2: human-readable time difference. */
					esc_html__( 'Requested %1$s, %2$s ago. A request from the site to itself can be answered differently from a visitor\'s, so confirm from outside before acting. WP Logs does not change server configuration; to block the file, add a rule like one of these:', 'swpl' ),
					'<code>' . esc_html( $result['url'] ) . '</code>',
					esc_html( human_time_diff( (int) $result['checked_at'] ) )
				);
				?>
			</p>
			<p><?php esc_html_e( 'Apache, in the .htaccess file of the folder that holds the log:', 'swpl' ); ?></p>
			<pre style="margin:0 0 8px;padding:8px;background:#f6f7f7;overflow:auto;"><?php echo esc_html( $apache ); ?></pre>
			<p><?php esc_html_e( 'nginx, inside the site\'s server block:', 'swpl' ); ?></p>
			<pre style="margin:0 0 8px;padding:8px;background:#f6f7f7;overflow:auto;"><?php echo esc_html( $nginx ); ?></pre>
			<p>
				<a class="button" href="<?php echo esc_url( $recheck_url ); ?>"><?php esc_html_e( 'Check again', 'swpl' ); ?></a>
				<a class="button-link" style="margin-left:8px;" href="<?php echo esc_url( $dismiss_url ); ?>"><?php esc_html_e( 'Dismiss', 'swpl' ); ?></a>
			</p>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Path shown in the notice: relative to ABSPATH when it is under it.
	 *
	 * @param string $path Debug log path.
	 * @return string
	 */
	public static function display_path( $path ) {
		$path    = wp_normalize_path( $path );
		$abspath = trailingslashit( wp_normalize_path( ABSPATH ) );

		if ( 0 === strpos( $path, $abspath ) ) {
			return substr( $path, strlen( $abspath ) );
		}

		return $path;
	}

	/**
	 * Dismiss the notice for the current log URL.
	 *
	 * @return void
	 */
	public static function handle_dismiss() {
		self::guard( self::DISMISS_ACTION );

		$result = DebugLogExposure::get_result();
		if ( $result && '' !== $result['url'] ) {
			DebugLogExposure::dismiss( $result['url'] );
		}

		self::back();
	}

	/**
	 * Drop the cached result and check again.
	 *
	 * @return void
	 */
	public static function handle_recheck() {
		self::guard( self::RECHECK_ACTION );

		DebugLogExposure::get_result( true );

		self::back();
	}

	/**
	 * Capability and nonce check for the admin-post handlers.
	 *
	 * @param string $action Nonce action.
	 * @return void
	 */
	protected static function guard( $action ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to do that.', 'swpl' ), 403 );
		}

		check_admin_referer( $action );
	}

	/**
	 * Redirect back to the Logs screen.
	 *
	 * @return void
	 */
	protected static function back() {
		wp_safe_redirect( admin_url( 'admin.php?page=shazzad-wp-logs' ) );
		exit;
	}
}
