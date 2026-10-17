<?php
/**
 * Check whether the WordPress debug log answers HTTP requests.
 *
 * @package Shazzad\WpLogs
 */

namespace Shazzad\WpLogs;

/**
 * Debug log exposure check.
 *
 * Sends a HEAD request from this server to the URL the debug log would have
 * if it sits under a web-served directory, and records what came back.
 *
 * The result is an observation, not a verdict. A request from the site to
 * itself can be answered differently from a request by a visitor (loopback
 * blocks, WAFs, local proxies), so the statuses say what was seen:
 *
 * - answered          HTTP 200 for the log, not served as text/html, and
 *                     HTTP 403, 404 or 410 for a file that does not exist
 *                     next to it (the control request).
 * - refused           HTTP 401, 403, 404 or 410 for the log.
 * - unknown           Anything else: a transport error or timeout, another
 *                     status code for the log, a 200 served as text/html (a
 *                     custom error or login page), or a control request that
 *                     failed or got any answer other than 403, 404 or 410.
 *                     Never read this as "safe".
 * - outside_web_root  The log is not under ABSPATH, WP_CONTENT_DIR or the
 *                     server document root, so it has no URL to request.
 * - no_file           There is no log file at the resolved path.
 *
 * Network results are cached for a day in a transient keyed by the md5 of
 * the resolved URL, so a changed log path gets a fresh check.
 *
 * @since n.e.x.t
 */
class DebugLogExposure {
	const STATUS_ANSWERED         = 'answered';
	const STATUS_REFUSED          = 'refused';
	const STATUS_UNKNOWN          = 'unknown';
	const STATUS_OUTSIDE_WEB_ROOT = 'outside_web_root';
	const STATUS_NO_FILE          = 'no_file';

	/**
	 * Transient prefix; the md5 of the resolved URL is appended.
	 */
	const TRANSIENT_PREFIX = 'swpl_dle_';

	/**
	 * Option holding the transient key of the last URL that was checked,
	 * so its cached result can be dropped when the log path changes.
	 */
	const LAST_KEY_OPTION = 'swpl_debug_log_exposure_last_key';

	/**
	 * Option holding the md5 of the URL whose "answered" notice was dismissed.
	 */
	const DISMISSED_OPTION = 'swpl_debug_log_exposure_dismissed';

	/**
	 * Cache lifetime for a network result.
	 */
	const CACHE_TTL = DAY_IN_SECONDS;

	/**
	 * Seconds to wait for each HEAD request.
	 */
	const TIMEOUT = 5;

	/**
	 * Control answers that show the server does not serve 200 for every path.
	 * 403 counts: a server that forbids a missing file is not a catch-all.
	 */
	const CONTROL_NOT_FOUND_CODES = [ 403, 404, 410 ];

	/**
	 * Whether the check should run at all.
	 *
	 * @return bool
	 */
	public static function is_enabled() {
		/**
		 * Filters whether the debug log exposure check runs.
		 *
		 * Return false on hosts where a request from the site to itself cannot
		 * tell you anything (loopback blocked or rewritten).
		 *
		 * @since n.e.x.t
		 * @param bool $enabled Default true.
		 */
		return (bool) apply_filters( 'swpl_debug_log_exposure_check', true );
	}

	/**
	 * Get the exposure result, from cache when possible.
	 *
	 * @param bool $force Skip the cache and request again.
	 * @return array|null Result array, or null when the check is disabled.
	 */
	public static function get_result( $force = false ) {
		if ( ! self::is_enabled() ) {
			return null;
		}

		$path = WpDebugLog::get_log_file();

		if ( ! $path || ! @is_file( $path ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors -- open_basedir.
			return self::make_result( self::STATUS_NO_FILE, $path );
		}

		$url = self::path_to_url( $path );

		if ( '' === $url ) {
			return self::make_result( self::STATUS_OUTSIDE_WEB_ROOT, $path );
		}

		$key = self::cache_key( $url );

		// The path (and so the URL) changed since the last check: drop the old result.
		$last_key = get_option( self::LAST_KEY_OPTION );
		if ( $last_key && $last_key !== $key ) {
			delete_transient( $last_key );
		}
		if ( $last_key !== $key ) {
			update_option( self::LAST_KEY_OPTION, $key, false );
		}

		if ( ! $force ) {
			$cached = get_transient( $key );
			if ( is_array( $cached ) && isset( $cached['status'] ) ) {
				$cached['cached'] = true;
				return $cached;
			}
		}

		$result = self::probe( $url, $path );
		set_transient( $key, $result, self::CACHE_TTL );

		/*
		 * A dismissed "answered" notice comes back if the log is later seen
		 * refusing requests and then answering again - the administrator fixed
		 * it once, and it came undone.
		 */
		if ( self::STATUS_REFUSED === $result['status'] ) {
			delete_option( self::DISMISSED_OPTION );
		}

		return $result;
	}

	/**
	 * Drop the cached result for the current log URL.
	 *
	 * @return void
	 */
	public static function clear_cache() {
		$last_key = get_option( self::LAST_KEY_OPTION );
		if ( $last_key ) {
			delete_transient( $last_key );
		}

		$path = WpDebugLog::get_log_file();
		$url  = $path ? self::path_to_url( $path ) : '';
		if ( '' !== $url ) {
			delete_transient( self::cache_key( $url ) );
		}
	}

	/**
	 * Map a filesystem path to the URL it is served at, if any.
	 *
	 * Only paths under WP_CONTENT_DIR, ABSPATH or the server document root
	 * get a URL. Anything else is treated as not web-reachable.
	 *
	 * @param string $path Absolute or relative filesystem path.
	 * @return string URL, or empty string when the path is outside the web root.
	 */
	public static function path_to_url( $path ) {
		$candidates = [ wp_normalize_path( $path ) ];

		$real = @realpath( $path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors -- open_basedir.
		if ( $real ) {
			$candidates[] = wp_normalize_path( $real );
		}

		$roots = [
			[ WP_CONTENT_DIR, content_url( '/' ) ],
			[ ABSPATH, site_url( '/' ) ],
		];

		$document_root = isset( $_SERVER['DOCUMENT_ROOT'] ) ? (string) $_SERVER['DOCUMENT_ROOT'] : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- compared as a path, never output.
		if ( '' !== $document_root ) {
			$home = wp_parse_url( home_url() );
			if ( ! empty( $home['host'] ) ) {
				$origin  = ( isset( $home['scheme'] ) ? $home['scheme'] : 'http' ) . '://' . $home['host'];
				$origin .= isset( $home['port'] ) ? ':' . $home['port'] : '';
				$roots[] = [ $document_root, $origin . '/' ];
			}
		}

		$url = '';

		foreach ( $roots as $root ) {
			$dirs = [ trailingslashit( wp_normalize_path( $root[0] ) ) ];

			$real_dir = @realpath( $root[0] ); // phpcs:ignore WordPress.PHP.NoSilencedErrors -- open_basedir.
			if ( $real_dir ) {
				$dirs[] = trailingslashit( wp_normalize_path( $real_dir ) );
			}

			foreach ( $dirs as $dir ) {
				foreach ( $candidates as $candidate ) {
					if ( 0 === strpos( $candidate, $dir ) ) {
						$relative = substr( $candidate, strlen( $dir ) );
						$url      = trailingslashit( $root[1] ) . implode( '/', array_map( 'rawurlencode', explode( '/', $relative ) ) );
						break 3;
					}
				}
			}
		}

		/**
		 * Filters the URL the debug log is requested at.
		 *
		 * Return an empty string to report the log as outside the web root.
		 *
		 * @since n.e.x.t
		 * @param string $url  Resolved URL, or empty string when none was found.
		 * @param string $path Debug log path.
		 */
		return (string) apply_filters( 'swpl_debug_log_exposure_url', $url, $path );
	}

	/**
	 * Transient key for a URL.
	 *
	 * @param string $url URL.
	 * @return string
	 */
	public static function cache_key( $url ) {
		return self::TRANSIENT_PREFIX . md5( $url );
	}

	/**
	 * Send the HEAD requests and classify the answer.
	 *
	 * @param string $url  Debug log URL.
	 * @param string $path Debug log path.
	 * @return array Result.
	 */
	protected static function probe( $url, $path ) {
		$response = self::head( $url );

		if ( is_wp_error( $response ) ) {
			return self::make_result(
				self::STATUS_UNKNOWN,
				$path,
				$url,
				null,
				sprintf( 'Request failed: %s', $response->get_error_message() )
			);
		}

		$code = (int) wp_remote_retrieve_response_code( $response );

		if ( in_array( $code, [ 401, 403, 404, 410 ], true ) ) {
			return self::make_result( self::STATUS_REFUSED, $path, $url, $code );
		}

		if ( 200 !== $code ) {
			return self::make_result(
				self::STATUS_UNKNOWN,
				$path,
				$url,
				$code,
				sprintf( 'Unexpected HTTP %d.', $code )
			);
		}

		/*
		 * A log file is served as text/plain or application/octet-stream. A
		 * 200 served as HTML is a page - typically a custom access-denied or
		 * login page some hosts send with a 200 - not the log. A header sent
		 * more than once comes back as an array; any HTML value counts.
		 */
		$content_types = [];
		foreach ( (array) wp_remote_retrieve_header( $response, 'content-type' ) as $content_type ) {
			$content_types[] = strtolower( trim( explode( ';', (string) $content_type )[0] ) );
		}

		if ( in_array( 'text/html', $content_types, true ) ) {
			return self::make_result(
				self::STATUS_UNKNOWN,
				$path,
				$url,
				$code,
				'The server answered HTTP 200 with text/html, which is a web page, not a log file.'
			);
		}

		/*
		 * A 200 only means something if the same server says "no such file"
		 * for a file that does not exist. Some hosts serve a soft 404 or a
		 * login page for every path; a control request that fails, is rate
		 * limited or errors says nothing about that either, so only a 403,
		 * 404 or 410 lets the 200 stand.
		 */
		$control_url      = trailingslashit( dirname( $url ) ) . 'swpl-probe-' . strtolower( wp_generate_password( 12, false ) ) . '.log';
		$control_response = self::head( $control_url );

		if ( is_wp_error( $control_response ) ) {
			return self::make_result(
				self::STATUS_UNKNOWN,
				$path,
				$url,
				$code,
				sprintf( 'The request for a file that does not exist failed: %s', $control_response->get_error_message() )
			);
		}

		$control_code = (int) wp_remote_retrieve_response_code( $control_response );

		if ( ! in_array( $control_code, self::CONTROL_NOT_FOUND_CODES, true ) ) {
			return self::make_result(
				self::STATUS_UNKNOWN,
				$path,
				$url,
				$code,
				sprintf( 'The server answered HTTP %d for a file that does not exist, so its HTTP 200 for the log tells nothing here.', $control_code )
			);
		}

		return self::make_result( self::STATUS_ANSWERED, $path, $url, $code );
	}

	/**
	 * One HEAD request. HEAD, not GET: the log body is never downloaded.
	 *
	 * @param string $url URL.
	 * @return array|\WP_Error
	 */
	protected static function head( $url ) {
		return wp_remote_head(
			$url,
			[
				'timeout'     => self::TIMEOUT,
				'redirection' => 3,
				// Same rule core uses for its own loopback requests.
				'sslverify'   => apply_filters( 'https_local_ssl_verify', false ),
			]
		);
	}

	/**
	 * Build a result array.
	 *
	 * @param string   $status Status constant.
	 * @param string   $path   Debug log path.
	 * @param string   $url    Requested URL.
	 * @param int|null $code   HTTP status code.
	 * @param string   $reason Human-readable detail.
	 * @return array
	 */
	protected static function make_result( $status, $path, $url = '', $code = null, $reason = '' ) {
		return [
			'status'     => $status,
			'path'       => (string) $path,
			'url'        => (string) $url,
			'code'       => $code,
			'reason'     => $reason,
			'checked_at' => time(),
			'cached'     => false,
		];
	}

	/**
	 * Whether the "answered" notice was dismissed for this URL.
	 *
	 * @param string $url Debug log URL.
	 * @return bool
	 */
	public static function is_dismissed( $url ) {
		return md5( $url ) === get_option( self::DISMISSED_OPTION );
	}

	/**
	 * Dismiss the "answered" notice for this URL.
	 *
	 * @param string $url Debug log URL.
	 * @return void
	 */
	public static function dismiss( $url ) {
		update_option( self::DISMISSED_OPTION, md5( $url ), false );
	}
}
