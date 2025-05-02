<?php
/**
 * Register rest api controllers
 * 
 * @package HomerunnerLocal
 */
namespace Shazzad\WpLogs;

class RestApi {
	public static function setup() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	public static function register_routes() {
		$classes = [
			__NAMESPACE__ . '\\RestController\\LogController',
		];

		foreach ( $classes as $class ) {
			$controller = new $class();
			$controller->register_routes();
		}
	}
}
