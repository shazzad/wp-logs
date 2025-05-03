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
						'id'    => 'swpl_max_logs_threshold',
						'name'  => 'swpl_max_logs_threshold',
						'label' => __( 'Max logs threshold', 'swpl' ),
						'desc'  => __( 'Set the maximum number of logs to be stored in the database. Default is 1000.', 'swpl' ),
						'type'  => 'number',
						'min'   => 0,
					),
					array(
						'id'    => 'swpl_max_requests_threshold',
						'name'  => 'swpl_max_requests_threshold',
						'label' => __( 'Max requests threshold', 'swpl' ),
						'desc'  => __( 'Set the maximum number of requests to be stored in the database. Default is 1000.', 'swpl' ),
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
			'swpl_max_logs_threshold'     => $this->get_setting( 'swpl_max_logs_threshold' ),
			'swpl_max_requests_threshold' => $this->get_setting( 'swpl_max_requests_threshold' ),
		);
	}

	public function get_defaults() {
		return array(
			'swpl_max_logs_threshold'     => '0',
			'swpl_max_requests_threshold' => '0',
		);
	}
}
