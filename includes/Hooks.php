<?php
/**
 * Hooks
 * 
 * @package Shazzad\WpLogs
 */
namespace Shazzad\WpLogs;

/**
 * Hooks class
 */
class Hooks {

	function __construct() {
		add_action( 'swpl_log', array( $this, 'store_log' ), 10, 4 );
		add_filter( 'swpl_format_message', array( $this, 'format_message' ), 20, 2 );
	}

	public function store_log( $source, $message, $context = array(), $level = 'info' ) {
		if ( empty( $message ) ) {
			return;
		}

		$log = new Log\Data();

		$log->set_source( $source );
		$log->set_message( $message );
		$log->set_context( $context );
		$log->set_level( $level );
		$log->save();
	}

	public function format_message( $message, $context = array() ) {
		if ( empty( $context ) ) {
			return $message;
		}

		$mustache = new \Mustache_Engine();
		return $mustache->render( $message, $context );
	}
}
