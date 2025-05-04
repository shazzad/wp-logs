<?php
namespace Shazzad\WpLogs\Log;

use Shazzad\WpLogs\Abstracts\Data as AbstractData;
use Shazzad\WpLogs\DbAdapter;

class Data extends AbstractData {

	protected $data = [
		'timestamp' => '',
		'level'     => '',
		'source'    => '',
		'message'   => '',
		'context'   => []
	];

	// fields than can be updated
	protected $updatable_fields = [];

	function __construct( $id = 0 ) {
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
	public function get_level() {
		return $this->get_prop( 'level' );
	}
	public function get_source() {
		return $this->get_prop( 'source' );
	}
	public function get_message() {
		return $this->get_prop( 'message' );
	}
	public function get_context() {
		return $this->get_prop( 'context' );
	}

	public function set_timestamp( $value ) {
		return $this->set_prop( 'timestamp', $value );
	}
	public function set_level( $value ) {
		return $this->set_prop( 'level', $value );
	}
	public function set_source( $value ) {
		return $this->set_prop( 'source', trim( $value ) );
	}
	public function set_message( $value ) {
		return $this->set_prop( 'message', $value );
	}
	public function set_context( $value ) {
		return $this->set_prop( 'context', $value );
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

		$id = DbAdapter::insert( DbAdapter::prefix_table( 'logs' ), $data );

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
				DbAdapter::prefix_table( 'logs' ),
				$data,
				[ 'id' => $this->get_id() ]
			);
		}

		$this->apply_changes();
		$this->clear_caches();
	}

	public function delete() {
		if ( ! $this->get_id() ) {
			throw new \Exception( __( 'Log not exists' ) );
		}

		do_action( 'swpl_log_delete', $this->get_id() );

		DbAdapter::delete( DbAdapter::prefix_table( 'logs' ), array( 'id' => $this->get_id() ) );

		do_action( 'swpl_log_deleted', $this->get_id() );
	}

	public function validate_save() {
		if ( ! $this->get_message() ) {
			throw new \Exception( __( 'Invalid message', 'swpl' ) );
		} else {
			if ( ! $this->get_timestamp() ) {
				// Let's use gmt timestamp.
				$this->set_timestamp( current_time( 'mysql', true ) );
			}

			if ( ! $this->get_level() ) {
				$this->set_level( 'info' );
			}
		}

		return true;
	}

	public function pre_save_filter( $data ) {
		if ( empty( $data['context'] ) ) {
			$data['context'] = [];
		}

		// Maximum size for mysql LONGTEXT field.
		$max_size = 4294967295;

		if ( is_array( $data['context'] ) ) {
			$data['context'] = $this->remove_size_recursive( $data['context'] );
		}

		$data['context'] = maybe_serialize( $data['context'] );

		if ( strlen( $data['context'] ) > $max_size ) {
			$data['context'] = maybe_serialize( 'REMOVED LARGE DATA' );
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

	public function pre_get_filter( $data ) {
		if ( array_key_exists( 'context', $data ) ) {
			$data['context'] = maybe_unserialize( $data['context'] );
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
