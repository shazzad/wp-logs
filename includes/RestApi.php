<?php
/**
 * Register REST API controllers
 *
 * This file handles the registration of REST API routes for the WP Logs plugin.
 * 
 * @package Shazzad\WpLogs
 * @since 1.0.0
 */
namespace Shazzad\WpLogs;

/**
 * Class RestApi
 *
 * Handles registration of REST API routes and controllers for the WP Logs plugin.
 *
 * @package Shazzad\WpLogs
 * @since 1.0.0
 */
class RestApi {
	/**
	 * Set up the REST API hooks
	 *
	 * Registers the action hooks needed for REST API functionality.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function setup() {
		add_action( 'rest_api_init', [ __CLASS__, 'register_routes' ] );
	}

	/**
	 * Register REST API routes
	 * 
	 * Initializes all REST API controllers and registers their routes.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function register_routes() {
		$classes = [
			__NAMESPACE__ . '\\RestController\\LogController',
			__NAMESPACE__ . '\\RestController\\RequestController',
			__NAMESPACE__ . '\\RestController\\SettingsController',
		];

		foreach ( $classes as $class ) {
			$controller = new $class();
			$controller->register_routes();
		}
	}
}
