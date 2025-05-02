<?php
/**
 * Rest api for property quote.
 *
 * @package HomerunnerLocal
 */
namespace Shazzad\WpLogs\RestController;

use Exception;
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
	 * Create quote
	 */
	public function get_items( $request ) {
		$query_args = [];

		$query = new Log\Query( $query_args );
		$query->query();

		$data = $query->get_objects();

		return new WP_REST_Response( $data, 201 );
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

		return new WP_Error( 'log_not_found', __( 'Log not found', 'homelocal' ), [ 'status' => 404 ] );
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
			return new WP_Error( 'log_not_found', __( 'Log not found', 'homelocal' ), [ 'status' => 404 ] );
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
