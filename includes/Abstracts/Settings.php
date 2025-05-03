<?php
/**
 * Abstract Settings Repository.
 *
 * @package HomerunnerLocal
 */
namespace Shazzad\WpLogs\Abstracts;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

abstract class Settings {

	protected $id = 'general';

	public static $priority = 0;

	public function get_id() {
		return $this->id;
	}

	abstract public function get_name();

	public function get_sections() {
		return array();
	}

	abstract public function get_fields();

	abstract public function get_settings();

	public function update_settings( $settings ) {
		foreach ( array_keys( $this->get_settings() ) as $key ) {
			if ( isset( $settings[$key] ) ) {
				$this->set_setting( $key, $settings[$key] );
			} else {
				$this->set_setting( $key, '' );
			}
		}

		$this->save();
	}

	public function store_default_settings() {
		$default_values = $this->get_fields_default_values( $this->get_fields() );

		$updated  = [];
		$settings = $this->get_settings();

		foreach ( array_keys( $settings ) as $key ) {
			if ( false === $this->get_setting( $key ) && isset( $default_values[$key] ) ) {
				$this->set_setting( $key, $default_values[$key] );
				$updated[$key] = $default_values[$key];
			}
		}

		$this->save();

		return $updated;
	}

	public function get_fields_default_values( $fields ) {
		$default_values = [];

		foreach ( $fields as $field ) {
			if ( isset( $field['default'] ) ) {
				$default_values[$field['id']] = $field['default'];
			}

			if ( isset( $field['type'] ) && 'section' === $field['type'] ) {
				$default_values = array_merge( $default_values, $this->get_fields_default_values( $field['fields'] ) );
			}
		}

		return $default_values;
	}

	/**
	 * Get setting value for a given key.
	 * 
	 * @param string $key
	 */
	public function get_setting( $key ) {
		return get_option( $key, $this->get_setting_default( $key ) );
	}

	/**
	 * Set setting value for a given key.
	 * 
	 * @param string $key
	 * @param mixed  $value
	 */
	public function set_setting( $key, $value = '' ) {
		$callback = "sanitize_{$key}";
		if ( method_exists( $this, $callback ) ) {
			$value = $this->$callback( $value );
		}

		update_option( $key, $value );
	}

	/**
	 * Delete setting value for a given key.
	 * 
	 * @param string $key
	 */
	public function delete_setting( $key ) {
		delete_option( $key );
	}

	/**
	 * Store settings.
	 */
	public function save() {
		return true;
	}

	public function get_schema() {
		return array(
			'sections' => $this->get_sections(),
			'fields'   => $this->get_fields(),
		);
	}

	public function get_defaults() {
		return [];
	}

	public function get_setting_default( $key ) {
		$defaults = $this->get_defaults();

		if ( isset( $defaults[$key] ) ) {
			return $defaults[$key];
		}

		return false;
	}
}
