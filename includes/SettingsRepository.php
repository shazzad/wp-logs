<?php
/**
 * Settings Repository.
 *
 * @package HomerunnerLocal
 */
namespace Shazzad\WpLogs;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * SettingsRepository class
 */
class SettingsRepository {

	public static $instance = null;

	protected $settings_classes = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
			self::$instance->load_settings_classes();
		}

		return self::$instance;
	}

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

	public function get_settings_classes() {
		return apply_filters( 'swpl_settings_classes', $this->settings_classes );
	}

	public function get_rest_namespace() {
		return 'swpl/v1';
	}

	public function get_rest_base() {
		return 'settings';
	}

	public function get_settings_rest_url( $setting_id ) {
		return rest_url( $this->get_rest_namespace() . '/' . $this->get_rest_base() . '/' . $setting_id );
	}
}
