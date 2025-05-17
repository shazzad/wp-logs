<?php
/**
 * Hooks
 * 
 * @package Shazzad\WpLogs
 */
namespace Shazzad\WpLogs;

use Exception;
use Mustache_Engine;

/**
 * Hooks class for Shazzad WP Logs.
 *
 * Registers WordPress actions and filters to handle logging and HTTP API debugging.
 *
 * @package Shazzad\WpLogs
 */
class Hooks {

	/**
	 * Registers WordPress actions and filters for logging and HTTP request logging.
	 *
	 * @return void
	 */
	public static function setup() {
		add_filter( 'swpl_log_request', [ __CLASS__, 'filter_allowed_urls' ], 10, 2 );
		add_action( 'swpl_log', [ __CLASS__, 'store_log' ], 10, 4 );
		add_action( 'http_api_debug', [ __CLASS__, 'maybe_store_http_request' ], 10, 5 );
	}

	/**
	 * Handles the 'swpl_log' action to store a log entry.
	 *
	 * @param string $source  Source of the log entry.
	 * @param string $message Log message.
	 * @param array  $context Contextual data for the log entry.
	 * @param string $level   Log level (default 'info').
	 * @return void
	 */
	public static function store_log( $source, $message, $context = [], $level = 'info' ) {
		if ( empty( $message ) ) {
			return;
		}

		try {
			$log = new Log\Data();

			$log->set_source( $source );
			$log->set_context( $context );
			$log->set_level( $level );
			$log->set_message_raw( $message );

			if ( ! empty( $message ) && ! empty( $context ) ) {
				$mustache = new Mustache_Engine();
				$message  = $mustache->render( $message, $context );
			}

			$log->set_message( $message );

			$log->save();
		}
		catch (Exception $e) {
			error_log( 'Error saving log: ' . $e->getMessage() );
		}
	}

	/**
	 * Handles the 'http_api_debug' action to conditionally store HTTP request and response details.
	 *
	 * @param mixed  $response    HTTP response array or WP_Error.
	 * @param string $str         Debug identifier string (unused).
	 * @param string $request     Raw request string (unused).
	 * @param array  $parsed_args Parsed request arguments including method, headers, and body.
	 * @param string $url         Request URL.
	 * @return void
	 */
	public static function maybe_store_http_request( $response, $str, $request, $parsed_args, $url ) {
		$enabled = apply_filters( 'swpl_log_request', false, $url );

		if ( ! $enabled ) {
			return;
		}

		if ( is_wp_error( $response ) ) {
			$props = [
				'request_method'  => $parsed_args['method'],
				'request_url'     => $url,
				'request_headers' => $parsed_args['headers'],
				'request_payload' => $parsed_args['body'],
				'response_code'   => 0,
				'response_data'   => $response,
			];
		} else {
			if ( ! isset( $response['response'] ) ) {
				return;
			}

			$props = [
				'request_method'   => $parsed_args['method'],
				'request_url'      => $url,
				'request_headers'  => $parsed_args['headers'],
				'request_payload'  => $parsed_args['body'],
				'response_code'    => $response['response']['code'],
				'response_headers' => $response['headers']->getAll(),
				'response_data'    => $response['body'],
			];
		}

		// Check if request payload is JSON, and decode it if necessary.
		if ( ! empty( $props['request_payload'] ) && ! is_array( $props['request_payload'] ) ) {
			$props['request_payload'] = json_decode( $props['request_payload'], true );

			// If decoding fails, set it to an error message.
			// json_last_error() will return JSON_ERROR_NONE if decoding was successful.
			if ( json_last_error() !== JSON_ERROR_NONE || null === $props['request_payload'] ) {
				$props['request_payload'] = [ 'error' => 'Invalid JSON' ];
			}
		}

		if ( ! empty( $props['response_headers'] ) ) {
			$headers = $props['response_headers'];
			if ( is_array( $headers ) ) {
				$headers = array_change_key_case( $headers );
			}

			$content_type = ! empty( $headers['content-type'] ) ? $headers['content-type'] : '';

			// If response header content type is json, decode it.
			if ( strpos( $content_type, 'application/json' ) !== false ) {
				$props['response_data'] = json_decode( $props['response_data'], true );
			}
		}

		// Remove sensitive data from request payload, headers, and response data.
		$props['request_payload']  = self::sanitize_data( $props['request_payload'] );
		$props['request_headers']  = self::sanitize_data( $props['request_headers'] );
		$props['response_data']    = self::sanitize_data( $props['response_data'] );
		$props['response_headers'] = self::sanitize_data( $props['response_headers'] );

		try {
			$request = new Request\Data();

			$request->set_props( $props );

			$request->save();
		}
		catch (Exception $e) {
			error_log( 'Error saving request: ' . $e->getMessage() );
		}
	}

	/**
	 * Sanitizes sensitive data in the request payload, headers, and response data.
	 *
	 * @param mixed $data The data to sanitize.
	 * @return mixed Sanitized data.
	 */
	public static function sanitize_data( $data ) {
		$regex = '/\b(?:password|secret|token|authorization|x-api-key)\b/i';

		if ( is_array( $data ) ) {
			foreach ( $data as $key => $value ) {
				if ( is_string( $value ) || is_numeric( $value ) ) {
					if ( preg_match( $regex, $key ) ) {
						$data[$key] = substr( $value, 0, 3 ) . str_repeat( '*', strlen( $value ) - 3 ) . ' (masked)';
					}
				} elseif ( is_array( $value ) || is_object( $value ) ) {
					$data[$key] = self::sanitize_data( $value );
				}
			}
		} elseif ( is_string( $data ) && preg_match( $regex, $data ) ) {
			return substr( $data, 0, 3 ) . str_repeat( '*', strlen( $data ) - 3 ) . ' (masked)';
		}

		return $data;
	}

	/**
	 * Filters the allowed URLs for logging.
	 *
	 * @param bool   $allow Whether to allow logging for the URL.
	 * @param string $url   The URL being checked.
	 * @return bool Whether to allow logging for the URL.
	 */
	public static function filter_allowed_urls( $allow, $url ) {
		$allowed_urls = get_option( 'swpl_logged_request_urls', '' );

		if ( ! empty( $allowed_urls ) ) {
			$allowed_urls = array_map( 'trim', explode( "\n", $allowed_urls ) );
			$allowed_urls = array_filter( $allowed_urls );

			foreach ( $allowed_urls as $allowed_url ) {
				if ( strpos( $url, $allowed_url ) !== false ) {
					$allow = true;
					break;
				}
			}
		}

		return $allow;
	}
}