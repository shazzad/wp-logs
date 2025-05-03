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
 * Hooks class
 */
class Hooks {

	public static function setup() {
		add_action( 'swpl_log', [ __CLASS__, 'store_log' ], 10, 4 );
		add_filter( 'swpl_format_message', [ __CLASS__, 'format_message' ], 20, 2 );
		add_action( 'http_api_debug', [ __CLASS__, 'maybe_store_http_request' ], 10, 5 );
	}

	public static function store_log( $source, $message, $context = [], $level = 'info' ) {
		if ( empty( $message ) ) {
			return;
		}

		try {
			$log = new Log\Data();

			$log->set_source( $source );
			$log->set_message( $message );
			$log->set_context( $context );
			$log->set_level( $level );
			$log->save();
		}
		catch (Exception $e) {
			error_log( 'Shazzad Wp Logs: ' . $e->getMessage() );
		}
	}

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
				'response_code'   => $response->get_error_code(),
				'response_data'   => $response->get_error_message(),
			];
		} else {
			if ( ! isset( $response['response'] ) ) {
				return;
			}
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

		try {
			$request = new Request\Data();

			$request->set_props( $props );
			$request->save();
		}
		catch (Exception $e) {
			error_log( 'Shazzad Wp Logs: ' . $e->getMessage() );
		}
	}

	public static function format_message( $message, $context = [] ) {
		if ( empty( $context ) ) {
			return $message;
		}

		$mustache = new Mustache_Engine();
		return $mustache->render( $message, $context );
	}
}