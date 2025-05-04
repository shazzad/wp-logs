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
use Shazzad\WpLogs\Request;
use Shazzad\WpLogs\DbAdapter;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * RequestController class for handling REST API endpoints for requests management.
 * 
 * Provides endpoints to fetch, view, and delete log entries.
 * 
 * @since 1.0.0
 */
class RequestController extends WP_REST_Controller {

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
	protected $rest_base = 'requests';

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

		register_rest_route(
			$this->namespace,
			$this->rest_base . '/(?P<id>[\d]+)',
			[
				[
					'methods'             => 'GET',
					'callback'            => [ $this, 'get_item' ],
					'permission_callback' => [ $this, 'get_item_permission_callback' ],
				],
				[
					'methods'             => 'DELETE',
					'callback'            => [ $this, 'delete_item' ],
					'permission_callback' => [ $this, 'delete_item_permission_callback' ],
				],
			]
		);
	}

	/**
	 * Retrieves a collection of log items with support for filtering and pagination.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_items( $request ) {
		$query_args = [
			'page'     => $request['page'] ?? 1,
			'per_page' => $request['per_page'] ?? 10,
			'orderby'  => $request['orderby'] ?? 'id',
			'order'    => $request['order'] ?? 'DESC',
			's'        => $request['search'] ?? '',
		];

		if ( ! empty( $request['request_method'] ) ) {
			$query_args['request_method'] = $request['request_method'];
		}

		if ( ! empty( $request['request_hostname'] ) ) {
			$query_args['request_hostname'] = $request['request_hostname'];
		}

		if ( ! empty( $request['response_code'] ) ) {
			$query_args['response_code'] = $request['response_code'];
		}


		if ( ! empty( $query_args['s'] ) ) {
			$query_args['s'] = '"' . $query_args['s'] . '"';
		}

		if ( ! empty( $request['fields'] ) ) {
			$fields = $this->get_fields( $request );

			if ( empty( $fields ) ) {
				return new WP_Error( 'invalid_fields', __( 'Invalid fields', 'swpl' ), [ 'status' => 400 ] );
			}

			$fields = array_unique( $fields );

			$query_args['columns'] = $fields;
		}

		$query = new Request\Query( $query_args );
		$query->query();

		$items       = $query->get_objects();
		$total       = $query->found_items;
		$total_pages = $query->max_num_pages;

		$data = [];
		foreach ( $items as $item ) {
			$data[] = $this->prepare_item( $item, $request );
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
	 * Retrieves a single log item.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_item( $request ) {
		$query = new Request\Query( [ 'id' => $request['id'] ] );
		$query->query();

		$items = $query->get_objects();

		if ( empty( $items ) ) {
			return new WP_Error( 'request_not_found', __( 'Log not found', 'swpl' ), [ 'status' => 404 ] );
		}

		$item = $items[0];

		return new WP_REST_Response( [ 'data' => $this->prepare_item( $item, $request ) ], 200 );
	}

	/**
	 * Deletes a single log item.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function delete_item( $request ) {
		$table = DbAdapter::prefix_table( 'requests' );

		$deleted = DbAdapter::delete( $table, [ 'id' => $request['id'] ] );

		if ( is_wp_error( $deleted ) ) {
			return $deleted;
		}

		if ( $deleted ) {
			return new WP_REST_Response( [ 'success' => true ], 200 );
		}

		return new WP_Error( 'request_not_found', __( 'Log not found', 'swpl' ), [ 'status' => 404 ] );
	}

	/**
	 * Deletes multiple log items.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function delete_items( $request ) {
		global $wpdb;

		$table = DbAdapter::prefix_table( 'requests' );

		if ( isset( $request['ids'] ) && is_array( $request['ids'] ) ) {
			$ids = array_map( 'intval', $request['ids'] );
			$ids = implode( ',', $ids );

			$sql = "DELETE FROM {$table} WHERE id IN ({$ids})";
			$wpdb->query( $sql );

			swpl_clear_cache();

			$deleted = true;

		} else {
			$sql = "DELETE FROM {$table}";

			$wpdb->query( $sql );

			swpl_clear_cache();

			$deleted = true;
		}

		if ( is_wp_error( $deleted ) ) {
			return $deleted;
		}

		if ( $deleted ) {
			return new WP_REST_Response( [ 'success' => true ], 200 );
		}

		return new WP_Error( 'requests_not_found', __( 'Logs not found', 'swpl' ), [ 'status' => 404 ] );
	}

	/**
	 * Prepares a log item for response.
	 *
	 * @since 1.0.0
	 * @param object          $item    Log item object.
	 * @param WP_REST_Request $request Request object.
	 * @return array Response data for the log item.
	 */
	protected function prepare_item( $item, $request ) {
		$fields = $this->get_fields( $request );

		if ( empty( $fields ) ) {
			return [];
		}

		$data = [];

		foreach ( $fields as $field ) {
			switch ( $field ) {
				case 'id':
					$data['id'] = $item->get_id();
					break;
				case 'date_created':
					$data['date_created'] = $item->get_date_created();
					break;
				case 'request_url':
					$data['request_url'] = $item->get_request_url();
					break;
				case 'request_payload':
					$data['request_payload'] = $item->get_request_payload();
					break;
				case 'request_headers':
					$data['request_headers'] = $item->get_request_headers();
					break;
				case 'request_method':
					$data['request_method'] = $item->get_request_method();
					break;
				case 'response_code':
					$data['response_code'] = $item->get_response_code();
					break;
				case 'response_size':
					$data['response_size'] = $item->get_response_size();
					break;
				case 'response_headers':
					$data['response_headers'] = $item->get_response_headers();
					break;
				case 'response_data':
					$data['response_data'] = $item->get_response_data();
					break;
			}
		}

		return $data;
	}

	protected function get_fields( $request ) {
		$allowed_fields = [
			'id',
			'date_created',
			'request_url',
			'request_payload',
			'request_headers',
			'request_method',
			'response_code',
			'response_size',
			'response_headers',
			'response_data',
		];

		if ( ! empty( $request['fields'] ) ) {
			$fields = explode( ',', $request['fields'] );
			$fields = array_map( 'trim', $fields );
			$fields = array_map( 'sanitize_key', $fields );

			$fields = array_intersect( $fields, $allowed_fields );

			if ( empty( $fields ) ) {
				return [];
			}

			return $fields;
		}

		return $allowed_fields;
	}


	/**
	 * Checks whether a user has permission to list requests.
	 * 
	 * @since 1.0.0
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool True if the request has permission to view requests, false otherwise.
	 */
	public function get_items_permission_callback( $request ) {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Checks whether a user has permission to view a specific log item.
	 * 
	 * @since 1.0.0
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool True if the request has permission to view the log item, false otherwise.
	 */
	public function get_item_permission_callback( $request ) {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Checks whether a user has permission to delete a specific log item.
	 * 
	 * @since 1.0.0
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool True if the request has permission to delete the log item, false otherwise.
	 */
	public function delete_item_permission_callback( $request ) {
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
