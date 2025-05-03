<?php
/**
 * Abstract rest api controller for sync request handler.
 *
 * @package Shazzad\WpLogs
 */
namespace Shazzad\WpLogs\RestController;

use WP_Error;
use WP_REST_Controller;
use WP_REST_Request;
use Shazzad\WpLogs\SettingsRepository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * SettingsController class
 */
class SettingsController extends WP_REST_Controller {

	public function __construct() {
		$this->namespace = SettingsRepository::instance()->get_rest_namespace();
		$this->rest_base = SettingsRepository::instance()->get_rest_base();
	}

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
					'callback'            => array( $this, 'get_settings_groups' ),
					'permission_callback' => array( $this, 'get_settings_groups_permission_callback' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			$this->rest_base . '/(?P<id>[\w-]+)',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_settings' ),
					'permission_callback' => array( $this, 'get_settings_permission_callback' ),
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'update_settings' ),
					'permission_callback' => array( $this, 'update_settings_permission_callback' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			$this->rest_base . '/(?P<id>[\w-]+)/schema',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_settings_schema' ),
					'permission_callback' => array( $this, 'get_settings_permission_callback' ),
				),
			)
		);
	}

	public function get_settings_groups( WP_REST_Request $request ) {
		$settings_groups = [];
		foreach ( SettingsRepository::instance()->get_settings_classes() as $setting_class ) {
			$settings_groups[] = [
				'id'   => $setting_class::instance()->get_id(),
				'name' => $setting_class::instance()->get_name(),
			];
		}

		return $settings_groups;
	}

	/**
	 * Get settings value.
	 *
	 * @param WP_REST_Request $request Request object.
	 */
	public function get_settings( WP_REST_Request $request ) {
		$setting_class = $this->get_setting_class( $request );

		return $setting_class::instance()->get_settings();
	}

	/**
	 * Update settings value.
	 */
	public function update_settings( $request ) {

		$setting_class = $this->get_setting_class( $request );

		if ( $request->is_json_content_type() ) {
			// Decode JSON body into an associative array
			$settings = json_decode( $request->get_body(), true );
		} else {
			// If not JSON, assume it’s URL-encoded form data
			$settings = $request->get_params();
		}

		$setting_class::instance()->update_settings( $settings );

		return [ 'success' => true ];
	}

	public function get_settings_schema( $request ) {
		$setting_class = $this->get_setting_class( $request );

		return $setting_class::instance()->get_schema();
	}

	/**
	 * Check for required parameters.
	 */
	public function get_settings_permission_callback( $request ) {
		$setting_class = $this->get_setting_class( $request );
		if ( is_null( $setting_class ) ) {
			return new WP_Error( 'invalid_settings_id', __( 'Invalid settings id.', 'homelocal' ), array( 'status' => 404 ) );
		}

		return current_user_can( 'manage_options' );
	}

	/**
	 * Check for required parameters.
	 */
	public function update_settings_permission_callback( $request ) {
		$setting_class = $this->get_setting_class( $request );
		if ( is_null( $setting_class ) ) {
			return new WP_Error( 'invalid_settings_id', __( 'Invalid settings id.', 'homelocal' ), array( 'status' => 404 ) );
		}

		return current_user_can( 'manage_options' );
	}

	/**
	 * Check for required parameters.
	 */
	public function get_settings_groups_permission_callback( $request ) {
		return current_user_can( 'manage_options' );
	}

	protected function get_setting_class( $request ) {
		$url_params  = $request->get_url_params();
		$settings_id = $url_params['id'];

		if ( empty( $settings_id ) ) {
			return new WP_Error( 'invalid_settings_id', __( 'Invalid settings id.', 'homelocal' ), array( 'status' => 404 ) );
		}

		$setting_class = null;

		foreach ( SettingsRepository::instance()->get_settings_classes() as $class ) {
			$settings = $class::instance();

			if ( $settings->get_id() === $settings_id ) {
				$setting_class = $class;
				break;
			}
		}

		return $setting_class;
	}
}
