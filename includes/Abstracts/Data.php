<?php
namespace Shazzad\WpLogs\Abstracts;

abstract class Data {
	protected $id = 0;
	protected $changes = [];
	protected $object_read = false;

	protected $data = [];
	protected $default_data = [];
	protected $extra_data = [];

	protected $meta_type = 'post';
	protected $meta_fields = [];
	protected $extra_meta_fields = [];

	public function __construct( $id = 0 ) {
		$this->data         = array_merge( $this->data, $this->extra_data );
		$this->meta_fields  = array_merge( $this->meta_fields, $this->extra_meta_fields );
		$this->default_data = $this->data;
	}

	public function set_id( $id ) {
		$this->id = absint( $id );
	}

	public function get_id() {
		return $this->id;
	}

	public function __toString() {
		return json_encode( $this->get_data() );
	}

	public function get_data() {
		return array_merge( [ 'id' => $this->get_id() ], $this->data );
	}

	public function get_extra_data_() {
		return $this->extra_data;
	}

	public function get_data_keys() {
		return array_keys( $this->data );
	}

	public function get_extra_data_keys() {
		return array_keys( $this->extra_data );
	}

	public function set_defaults() {
		$this->data    = $this->default_data;
		$this->changes = [];
		$this->set_object_read( false );
	}

	public function set_object_read( $read = true ) {
		$this->object_read = (bool) $read;
	}

	public function set_props( $props, $context = 'set' ) {
		$errors = [];

		foreach ( $props as $prop => $value ) {
			try {
				$setter = "set_$prop";
				if ( ! is_null( $value ) && is_callable( [ $this, $setter ] ) ) {
					$reflection = new \ReflectionMethod( $this, $setter );
					if ( $reflection->isPublic() ) {
						$this->{$setter}( $value );
					}
				}
			}
			catch (\Exception $e) {
				$errors[] = $e->getMessage();
			}
		}

		return sizeof( $errors ) ? $errors : true;
	}

	protected function pre_set_props( $props, $id ) {
		return $props;
	}

	protected function set_prop( $prop, $value ) {
		if ( array_key_exists( $prop, $this->data ) ) {
			if ( true === $this->object_read ) {
				if ( $value !== $this->data[ $prop ] || array_key_exists( $prop, $this->changes ) ) {
					$this->changes[ $prop ] = $value;
				}
			} else {
				$this->data[ $prop ] = $value;
			}
		}
	}

	public function get_changes() {
		return $this->changes;
	}

	public function apply_changes() {
		$this->data    = array_replace_recursive( $this->data, $this->changes );
		$this->changes = [];
	}

	protected function get_prop( $prop, $context = 'view' ) {
		$value = null;
		if ( array_key_exists( $prop, $this->data ) ) {
			$value = array_key_exists( $prop, $this->changes ) ? $this->changes[ $prop ] : $this->data[ $prop ];
		}

		return $value;
	}

	public function read_metadata( $id ) {
		$metadata = [];
		foreach ( $this->meta_fields as $field => $args ) {
			$metadata[ $field ] = get_metadata( $this->meta_type, $id, $args['key'], $args['unique'] );
		}
		return $metadata;
	}

	public function update_metadata( $data ) {
		foreach ( $this->meta_fields as $field => $args ) {
			if ( array_key_exists( $field, $data ) ) {
				if ( $args['unique'] ) {
					update_metadata( $this->meta_type, $this->get_id(), $args['key'], $data[ $field ], '' );
				} else {
					delete_metadata( $this->meta_type, $this->get_id(), $args['key'], '' );
					if ( is_array( $data[ $field ] ) ) {
						foreach ( $data[ $field ] as $val ) {
							add_metadata( $this->meta_type, $this->get_id(), $args['key'], $val, false );
						}
					}
				}
			}
		}
	}

	public function delete() {
	}

	public function save() {
	}

	public function clear_caches() {
	}

	public function pre_get_filter( $data ) {
		return $data;
	}

	public function pre_save_filter( $data ) {
		return $data;
	}
}
