<?php
/**
 * Rest api for property quote.
 *
 * @package HomerunnerLocal
 */
namespace Shazzad\WpLogs\RestController;

use WP_Error;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use Shazzad\WpLogs\Log;
use Shazzad\WpLogs\DbAdapter;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * QuoteController class.
 */
class LogController extends WP_REST_Controller {

	protected $namespace = 'wp/v2';

	protected $rest_base = 'logs';

	/**
	 * Register routes.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			$this->rest_base,
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permission_callback' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			$this->rest_base . '/(?P<id>[\d]+)',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permission_callback' ),
				),
				array(
					'methods'             => 'DELETE',
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'delete_item_permission_callback' ),
				),
			)
		);
	}

	/**
	 * Get log items with pagination
	 * 
	 * @param WP_REST_Request $request
	 * 
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_items( $request ) {
		$query_args = [
			'page'     => $request['page'] ?? 1,
			'per_page' => $request['per_page'] ?? 10,
			'orderby'  => $request['orderby'] ?? 'id',
			'order'    => $request['order'] ?? 'DESC',
			's'        => $request['search'] ?? '',
		];

		if ( ! empty( $request['source'] ) ) {
			$query_args['source'] = $request['source'];
		}

		if ( ! empty( $request['level'] ) ) {
			if ( ! array_key_exists( $request['level'], swpl_get_levels() ) ) {
				return new WP_Error( 'invalid_log_level', __( 'Invalid log level', 'shazzad-wp-logs' ), [ 'status' => 400 ] );
			}

			$query_args['level'] = strtolower( $request['level'] );
		}

		$query = new Log\Query( $query_args );
		$query->query();

		$items       = $query->get_objects();
		$total       = $query->found_items;
		$total_pages = $query->max_num_pages;

		$data = [];
		foreach ( $items as $item ) {
			$data[] = [
				'id'      => $item->get_id(),
				'date'    => $item->get_timestamp(),
				'level'   => $item->get_level(),
				'source'  => $item->get_source(),
				'message' => $item->get_message(),
				'context' => $item->get_context(),
			];
		}

		$response = new WP_REST_Response( [ 'data' => $data ], 200 );

		// Add pagination headers
		$response->header( 'X-WP-Total', $total );
		$response->header( 'X-WP-TotalPages', $total_pages );

		// Add pagination data to response
		$response->set_data( [
			'data' => $data,
			'meta' => [
				'total'       => $total,
				'total_pages' => $total_pages,
				'page'        => (int) $query_args['page'],
				'per_page'    => (int) $query_args['per_page'],
			]
		] );

		return $response;
	}

	/**
	 * Update quote
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_item( $request ) {
		$table = DbAdapter::prefix_table( 'logs' );

		$deleted = DbAdapter::delete( $table, [ 'id' => $request['id'] ] );

		if ( is_wp_error( $deleted ) ) {
			return $deleted;
		}

		if ( $deleted ) {
			return new WP_REST_Response( [ 'success' => true ], 200 );
		}

		return new WP_Error( 'log_not_found', __( 'Log not found', 'shazzad-wp-logs' ), [ 'status' => 404 ] );
	}

	/**
	 * Get quote
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_item( $request ) {
		$table = DbAdapter::prefix_table( 'logs' );

		$query = new Log\Query( [ 'id' => $request['id'] ] );
		$query->query();

		$items = $query->get_objects();

		if ( empty( $items ) ) {
			return new WP_Error( 'log_not_found', __( 'Log not found', 'shazzad-wp-logs' ), [ 'status' => 404 ] );
		}

		return new WP_REST_Response( $items[0], 200 );
	}

	/**
	 * Check for required parameters.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return bool|WP_Error
	 */
	public function get_items_permission_callback( $request ) {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Check for required parameters.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return bool|WP_Error
	 */
	public function get_item_permission_callback( $request ) {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Check for required parameters.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return bool|WP_Error
	 */
	public function delete_item_permission_callback( $request ) {
		return current_user_can( 'manage_options' );
	}
}
