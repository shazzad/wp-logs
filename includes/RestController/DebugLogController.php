<?php
/**
 * REST API controller for logs management.
 *
 * @package Shazzad\WpLogs
 * @since 1.0.0
 */
namespace Shazzad\WpLogs\RestController;

use WP_Error;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use Shazzad\WpLogs\WpDebugLog;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * DebugLogController class for handling REST API endpoints for logs management.
 * 
 * Provides endpoints to fetch, view, and delete log entries.
 * 
 * @since 1.0.0
 */
class DebugLogController extends WP_REST_Controller {

	/**
	 * The namespace of this controller's route.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $namespace = 'swpl/v1';

	/**
	 * The base of this controller's route.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $rest_base = 'debug-log';

	/**
	 * Register REST API routes for log management.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			$this->rest_base,
			[
				[
					'methods'             => 'GET',
					'callback'            => [ $this, 'get_items' ],
					'permission_callback' => [ $this, 'get_items_permission_callback' ],
				],
				[
					'methods'             => [ 'DELETE' ],
					'callback'            => [ $this, 'delete_items' ],
					'permission_callback' => [ $this, 'delete_items_permission_callback' ],
				],
			]
		);
	}

	/**
	 * Retrieves a collection of log items with support for filtering and pagination.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error|array Response object on success, or WP_Error object on failure.
	 */
	public function get_items( $request ) {
		$data = [
			'header' => __( 'WP Debug Log', 'swpl' )
		];

		if ( ! current_user_can( 'manage_options' ) ) {
			$data['content'] = __( 'Unauthorized Request', 'swpl' );
			return [ 'data' => $data ];
		}

		if ( ! file_exists( WpDebugLog::get_log_file() ) ) {
			$data['content'] = __( '<code>debug.log</code> file does not exists.', 'swpl' );
			return [ 'data' => $data ];
		}

		$content = file_get_contents( WpDebugLog::get_log_file() );
		if ( empty( $content ) ) {
			$data['content'] = __( '<code>debug.log</code> file empty.', 'swpl' );
			return [ 'data' => $data ];
		}

		$data['content'] = '<pre>' . $content . '</pre>';
		$data['footer']  = '<button type="button" class="button button-primary" id="swpl-wp-debug-log-delete-btn">Clear Logs</button>';

		return [ 'data' => $data ];
	}

	/**
	 * Deletes multiple log items.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function delete_items( $request ) {
		if ( ! file_exists( WpDebugLog::get_log_file() ) ) {
			return new WP_Error(
				'no_logs',
				__( 'No logs', 'swpl' ),
				[ 'status' => 404 ]
			);
		}

		unlink( WpDebugLog::get_log_file() );

		return new WP_REST_Response(
			[
				'message' => __( 'Deleted', 'swpl' )
			],
			200
		);
	}

	/**
	 * Checks whether a user has permission to list logs.
	 * 
	 * @since 1.0.0
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool True if the request has permission to view logs, false otherwise.
	 */
	public function get_items_permission_callback( $request ) {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Checks whether a user has permission to delete multiple log items.
	 * 
	 * @since 1.0.0
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool True if the request has permission to delete the log items, false otherwise.
	 */
	public function delete_items_permission_callback( $request ) {
		return current_user_can( 'manage_options' );
	}
}
