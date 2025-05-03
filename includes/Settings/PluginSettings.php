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

class PluginSettings extends Settings {

	protected $id = 'plugin_settings';

	public static $priority = 80;

	public static $instance = null;

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function get_name() {
		return __( 'Plugin Settings', 'swpl' );
	}

	public function get_fields() {
		$settings_fields = array(
			array(
				'id'          => 'swpl_user',
				'label'       => __( 'Guest Setting', 'swpl' ),
				'type'        => 'section',
				'collapsible' => true,
				'collapsed'   => false,
				'fields'      => array(
					array(
						'id'    => 'swpl_log_retention_days',
						'name'  => 'swpl_log_retention_days',
						'label' => __( 'Retain Logs For', 'swpl' ),
						'desc'  => __( 'Set the maximum number of days to retain logs. Default is 0, infinite.', 'swpl' ),
						'type'  => 'number',
						'min'   => 0,
					),
					array(
						'id'    => 'swpl_request_retention_days',
						'name'  => 'swpl_request_retention_days',
						'label' => __( 'Retain Requests For', 'swpl' ),
						'desc'  => __( 'Set the maximum number of days to retain requests. Default is 0, infinite.', 'swpl' ),
						'type'  => 'number',
						'min'   => 0,
					),
				)
			),
		);

		return $settings_fields;
	}

	public function get_settings() {
		return array(
			'swpl_log_retention_days'     => $this->get_setting( 'swpl_log_retention_days' ),
			'swpl_request_retention_days' => $this->get_setting( 'swpl_request_retention_days' ),
		);
	}

	public function get_defaults() {
		return array(
			'swpl_log_retention_days'     => '0',
			'swpl_request_retention_days' => '0',
		);
	}
}
