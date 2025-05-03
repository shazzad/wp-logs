<?php
/**
 * Settings Repository.
 *
 * Manages settings classes and provides utilities for settings-related operations.
 * This class is responsible for loading and organizing settings classes by priority
 * and providing REST API endpoints access.
 *
 * @package Shazzad\WpLogs
 * @since 1.0.0
 */
namespace Shazzad\WpLogs;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * SettingsRepository class
 *
 * @since 1.0.0
 */
class SettingsRepository {

	/**
	 * The singleton instance of the repository.
	 *
	 * @since 1.0.0
	 *
	 * @var self|null
	 */
	public static $instance = null;

	/**
	 * Array of registered settings class names.
	 *
	 * @since 1.0.0
	 *
	 * @var string[]
	 */
	protected $settings_classes = null;

	/**
	 * Get the singleton instance of the SettingsRepository.
	 *
	 * @since 1.0.0
	 *
	 * @return self The SettingsRepository instance.
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
			self::$instance->load_settings_classes();
		}

		return self::$instance;
	}

	/**
	 * Load all settings classes from the Settings directory and sort by priority.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	protected function load_settings_classes() {
		if ( is_null( $this->settings_classes ) ) {
			$this->settings_classes = [];
			foreach ( glob( __DIR__ . '/Settings/*.php' ) as $filename ) {
				require_once $filename;

				$this->settings_classes[] = __NAMESPACE__ . '\\Settings\\' . basename( $filename, '.php' );
			}

			// Order settings classes by priority.
			usort( $this->settings_classes, function ($a, $b) {
				return $a::$priority - $b::$priority;
			} );
		}
	}

	/**
	 * Retrieve the list of settings classes.
	 *
	 * @since 1.0.0
	 *
	 * @return string[] List of settings class names.
	 */
	public function get_settings_classes() {
		return apply_filters( 'swpl_settings_classes', $this->settings_classes );
	}

	/**
	 * Get the REST API namespace for settings endpoints.
	 *
	 * @since 1.0.0
	 *
	 * @return string REST API namespace.
	 */
	public function get_rest_namespace() {
		return 'swpl/v1';
	}

	/**
	 * Get the REST API base route for settings endpoints.
	 *
	 * @since 1.0.0
	 *
	 * @return string REST API base route.
	 */
	public function get_rest_base() {
		return 'settings';
	}

	/**
	 * Get the REST API URL for a specific setting.
	 *
	 * @since 1.0.0
	 *
	 * @param string $setting_id The setting identifier.
	 * @return string The full REST API URL for the setting.
	 */
	public function get_settings_rest_url( $setting_id ) {
		return rest_url( $this->get_rest_namespace() . '/' . $this->get_rest_base() . '/' . $setting_id );
	}
}
