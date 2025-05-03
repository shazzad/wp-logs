<?php
namespace Shazzad\WpLogs\Request;

use Shazzad\WpLogs\Abstracts\Data as AbstractData;
use Shazzad\WpLogs\DbAdapter;

class Data extends AbstractData {

	protected $data = [
		'timestamp'        => '',
		'source'           => '',
		'request_method'   => '',
		'request_url'      => '',
		'request_hostname' => '',
		'request_headers'  => '',
		'request_payload'  => '',
		'response_code'    => '',
		'response_headers' => '',
		'response_data'    => '',
		'response_time'    => '',
		'response_size'    => '',
	];

	// fields than can be updated
	protected $updatable_fields = [];

	public function __construct( $id = 0 ) {
		parent::__construct( $id );

		if ( is_numeric( $id ) && $id > 0 ) {
			$this->set_id( $id );
		} else if ( $id instanceof self ) {
			$this->set_id( $id->get_id() );
		} else {
			$this->set_object_read( true );
		}

		if ( $this->get_id() > 0 ) {
			$this->read();
		}
	}

	public function get_timestamp() {
		return $this->get_prop( 'timestamp' );
	}
	public function get_source() {
		return $this->get_prop( 'source' );
	}
	public function get_request_method() {
		return $this->get_prop( 'request_method' );
	}
	public function get_request_url() {
		return $this->get_prop( 'request_url' );
	}
	public function get_request_hostname() {
		return $this->get_prop( 'request_hostname' );
	}
	public function get_request_headers() {
		return $this->get_prop( 'request_headers' );
	}
	public function get_request_payload() {
		return $this->get_prop( 'request_payload' );
	}
	public function get_response_code() {
		return $this->get_prop( 'response_code' );
	}
	public function get_response_time() {
		return $this->get_prop( 'response_time' );
	}
	public function get_response_size() {
		return $this->get_prop( 'response_size' );
	}
	public function get_response_data() {
		return $this->get_prop( 'response_data' );
	}
	public function get_response_headers() {
		return $this->get_prop( 'response_headers' );
	}


	public function set_timestamp( $value ) {
		return $this->set_prop( 'timestamp', $value );
	}

	public function set_source( $value ) {
		return $this->set_prop( 'source', trim( $value ) );
	}

	public function set_request_method( $value ) {
		return $this->set_prop( 'request_method', trim( $value ) );
	}

	public function set_request_url( $value ) {
		return $this->set_prop( 'request_url', trim( $value ) );
	}

	public function set_request_hostname( $value ) {
		return $this->set_prop( 'request_hostname', trim( $value ) );
	}

	public function set_request_headers( $value ) {
		return $this->set_prop( 'request_headers', $value );
	}

	public function set_request_payload( $value ) {
		return $this->set_prop( 'request_payload', $value );
	}

	public function set_response_code( $value ) {
		return $this->set_prop( 'response_code', $value );
	}

	public function set_response_time( $value ) {
		return $this->set_prop( 'response_time', $value );
	}

	public function set_response_headers( $value ) {
		return $this->set_prop( 'response_headers', $value );
	}

	public function set_response_data( $value ) {
		return $this->set_prop( 'response_data', $value );
	}

	public function set_response_size( $value ) {
		return $this->set_prop( 'response_size', $value );
	}


	public function save() {
		if ( $this->get_id() > 0 ) {
			$this->update();
		} else {
			$this->create();
		}
	}
	public function read() {
		if ( $this->get_id() > 0 ) {
			$query = new Query( [
				'id'     => $this->get_id(),
				'method' => 'get_row',
				'output' => 'ARRAY_A'
			] );
			$query->query();
			$id_row = $query->get_results();
		}

		if ( ! empty( $id_row ) ) {
			$this->set_defaults();
			$this->set_props( $this->pre_get_filter( $id_row ) );
		} else {
			$this->set_id( 0 );
		}

		$this->set_object_read( true );
	}

	public function create() {
		$this->validate_save();

		$data = $this->get_changes();
		if ( array_key_exists( 'id', $data ) ) {
			unset( $data['id'] );
		}

		$data = $this->pre_save_filter( $data );

		$id = DbAdapter::insert( DbAdapter::prefix_table( 'requests' ), $data );

		$this->set_id( $id );
		$this->apply_changes();
		$this->clear_caches();
	}

	public function update() {
		$this->validate_save();

		$changes = $this->get_changes();

		if ( array_intersect( $this->updatable_fields, array_keys( $changes ) ) ) {
			$data = $this->get_changes();
			$data = $this->pre_save_filter( $data );

			DbAdapter::update(
				DbAdapter::prefix_table( 'requests' ),
				$data,
				[ 'id' => $this->get_id() ]
			);
		}

		$this->apply_changes();
		$this->clear_caches();
	}

	public function delete() {
		if ( ! $this->get_id() ) {
			throw new \Exception( __( 'Request not exists' ) );
		}

		do_action( 'shazzad_wp_logs/request/delete', $this->get_id() );

		DbAdapter::delete( DbAdapter::prefix_table( 'requests' ), array( 'id' => $this->get_id() ) );

		do_action( 'shazzad_wp_logs/request/deleted', $this->get_id() );
	}

	public function validate_save() {
		if ( ! $this->get_request_url() ) {
			throw new \Exception( __( 'Invalid url', 'shazzad-wp-logs' ) );
		}
		if ( ! $this->get_request_method() ) {
			throw new \Exception( __( 'Invalid request method', 'shazzad-wp-logs' ) );
		}

		if ( ! $this->get_timestamp() ) {
			// Let's use gmt timestamp.
			$this->set_timestamp( current_time( 'mysql', true ) );
		}

		// Generate hostname from url.
		$parsed_url = parse_url( $this->get_request_url() );

		if ( ! empty( $parsed_url['host'] ) ) {
			$this->set_request_hostname( $parsed_url['host'] );
		}

		if ( ! $this->get_response_size() ) {
			$headers = $this->get_response_headers();
			if ( is_array( $headers ) && array_key_exists( 'content-length', $headers ) ) {
				$this->set_response_size( $headers['content-length'] );
			} elseif ( is_numeric( $this->get_response_data() ) ) {
				$this->set_response_size( $this->get_response_data() );
			} elseif ( $this->get_response_data() ) {
				$this->set_response_size( strlen( $this->get_response_data() ) );
			}
		}
	}

	public function pre_get_filter( $data ) {
		foreach ( [ 'request_headers', 'request_payload', 'response_data', 'response_headers',] as $array_field ) {
			if ( array_key_exists( $array_field, $data ) ) {
				$data[$array_field] = maybe_unserialize( $data[$array_field] );
			}
		}

		return $data;
	}

	public function pre_save_filter( $data ) {
		foreach ( [ 'request_headers', 'request_payload', 'response_data', 'response_headers',] as $array_field ) {
			if ( empty( $data[$array_field] ) ) {
				$data[$array_field] = [];
			}

			// Maximum size for mysql LONGTEXT field.
			$max_size = 4294967295;

			// Maximum size for mysql TEXT field.
			// $max_size = 65535;

			if ( is_array( $data[$array_field] ) ) {
				$data[$array_field] = $this->remove_size_recursive( $data[$array_field] );
			}

			$data[$array_field] = maybe_serialize( $data[$array_field] );

			if ( strlen( $data[$array_field] ) > $max_size ) {
				$data[$array_field] = maybe_serialize( 'REMOVED LARGE DATA' );
			}
		}

		return $data;
	}

	protected function remove_size_recursive( $data, $filled = 0 ) {
		$max_chunk_size = 4294967295 / 20;

		if ( is_string( $data ) || is_numeric( $data ) ) {
			if ( strlen( $data ) > $max_chunk_size ) {
				$data = substr( $data, 0, 10 ) . ' REMOVED LARGE DATA';
			}

		} else if ( is_array( $data ) ) {
			foreach ( $data as $k => $v ) {
				$data[$k] = $this->remove_size_recursive( $v, $filled );
			}
		}

		return $data;
	}

	public static function load( $data ) {
		$self = new self();
		if ( is_object( $data ) ) {
			$data = get_object_vars( $data );
		}

		if ( ! empty( $data ) ) {
			$self->set_defaults();
			$self->set_props( $self->pre_get_filter( $data ) );
			$self->apply_changes();
		}

		$self->set_object_read( true );

		return $self;
	}
}
