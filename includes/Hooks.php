<?php
/**
 * Hooks
 * 
 * @package Shazzad\WpLogs
 */
namespace Shazzad\WpLogs;

use Exception;

/**
 * Hooks class
 */
class Hooks {
	function __construct() {
		add_action( 'swpl_log', [ $this, 'store_log' ], 10, 4 );
		add_filter( 'swpl_format_message', [ $this, 'format_message' ], 20, 2 );
	}

	public function store_log( $source, $message, $context = [], $level = 'info' ) {
		if ( empty( $message ) ) {
			return;
		}

		try {
			$log = new Log\Data();

			$log->set_source( $source );
			$log->set_message( $message );
			$log->set_context( $context );
			$log->set_level( $level );
			$log->save();
		}
		catch (Exception $e) {
			error_log( 'Shazzad Wp Logs: ' . $e->getMessage() );
		}
	}

	public function format_message( $message, $context = array() ) {
		if ( empty( $context ) ) {
			return $message;
		}

		$mustache = new \Mustache_Engine();
		return $mustache->render( $message, $context );
	}
}