<?php
/**
 * Reservation Settings Repository.
 *
 * @package Shazzad\WpLogs
 */
namespace Shazzad\WpLogs\Settings;

use Shazzad\WpLogs\Abstracts\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin Settings Repository.
 *
 * Provides the plugin settings fields, accessors, and default values.
 *
 * @package Shazzad\WpLogs\Settings
 */
class PluginSettings extends Settings {

	protected $id = 'plugin_settings';

	public static $priority = 80;

	public static $instance = null;

	/**
	 * Retrieves the singleton instance.
	 *
	 * @return self
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Get the settings page name.
	 *
	 * @return string Localized name of the settings page.
	 */
	public function get_name() {
		return __( 'Plugin Settings', 'swpl' );
	}

	/**
	 * Get the settings fields array.
	 *
	 * @return array Settings fields configuration.
	 */
	public function get_fields() {
		$settings_fields = [
			[
				'id'          => 'swpl_user',
				'label'       => __( 'Guest Setting', 'swpl' ),
				'type'        => 'section',
				'collapsible' => true,
				'collapsed'   => false,
				'fields'      => [
					[
						'id'    => 'swpl_log_retention_days',
						'name'  => 'swpl_log_retention_days',
						'label' => __( 'Retain Logs For', 'swpl' ),
						'desc'  => __( 'Set the maximum number of days to retain logs. Default is 7 days, minimum 1.', 'swpl' ),
						'type'  => 'number',
						'min'   => 1,
					],
					[
						'id'    => 'swpl_request_retention_days',
						'name'  => 'swpl_request_retention_days',
						'label' => __( 'Retain Requests For', 'swpl' ),
						'desc'  => __( 'Set the maximum number of days to retain requests. Default is 7 days, minimum 1.', 'swpl' ),
						'type'  => 'number',
						'min'   => 1,
					],
					[
						'id'    => 'swpl_logged_request_urls',
						'name'  => 'swpl_logged_request_urls',
						'label' => __( 'Request URLs to Log', 'swpl' ),
						'desc'  => __( 'Enter one URL pattern per line. HTTP requests matching these patterns will be logged.', 'swpl' ),
						'type'  => 'textarea',
					],
				]
			],
		];

		return $settings_fields;
	}

	/**
	 * Get current settings values.
	 *
	 * @return array Associative array of setting keys and their values.
	 */
	public function get_settings() {
		return [
			'swpl_log_retention_days'     => $this->get_setting( 'swpl_log_retention_days' ),
			'swpl_request_retention_days' => $this->get_setting( 'swpl_request_retention_days' ),
			'swpl_logged_request_urls'    => $this->get_setting( 'swpl_logged_request_urls' ),
		];
	}

	/**
	 * Get default settings values.
	 *
	 * @return array Associative array of setting keys and their default values.
	 */
	public function get_defaults() {
		return [
			'swpl_log_retention_days'     => '7',
			'swpl_request_retention_days' => '7',
			'swpl_logged_request_urls'    => '',
		];
	}

	/**
	 * Sanitize the log retention days setting.
	 *
	 * @param mixed $value Submitted value.
	 * @return string Number of days, minimum 1. Invalid input falls back to 7.
	 */
	public function sanitize_swpl_log_retention_days( $value ) {
		return $this->sanitize_retention_days( $value );
	}

	/**
	 * Sanitize the request retention days setting.
	 *
	 * @param mixed $value Submitted value.
	 * @return string Number of days, minimum 1. Invalid input falls back to 7.
	 */
	public function sanitize_swpl_request_retention_days( $value ) {
		return $this->sanitize_retention_days( $value );
	}

	/**
	 * Sanitize a retention days value.
	 *
	 * @param mixed $value Submitted value.
	 * @return string Number of days, minimum 1. Invalid input falls back to 7.
	 */
	private function sanitize_retention_days( $value ) {
		$days = (int) $value;

		if ( $days < 1 ) {
			$days = 7;
		}

		return (string) $days;
	}
}
