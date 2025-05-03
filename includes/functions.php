<?php

function swpl_get_sources() {
	$cache_key = 'swpl_sources';

	$sources = get_transient( $cache_key );

	if ( false === $sources ) {
		$table   = \Shazzad\WpLogs\DbAdapter::prefix_table( 'logs' );
		$sources = \Shazzad\WpLogs\DbAdapter::get_col( "SELECT DISTINCT source FROM $table" );

		set_transient( $cache_key, $sources, 5 * MINUTE_IN_SECONDS );
	}

	return $sources;
}

function swpl_get_levels() {
	return [
		'debug'     => __( 'Debug', 'swpl' ),
		'info'      => __( 'Info', 'swpl' ),
		'notice'    => __( 'Notice', 'swpl' ),
		'warning'   => __( 'Warning', 'swpl' ),
		'error'     => __( 'Error', 'swpl' ),
		'critical'  => __( 'Critical', 'swpl' ),
		'emergency' => __( 'Emergency', 'swpl' )
	];
}

function swpl_get_request_hostnames() {
	$cache_key = 'swpl_request_hostnames';

	$hostnames = get_transient( $cache_key );

	if ( false === $hostnames ) {
		$table     = \Shazzad\WpLogs\DbAdapter::prefix_table( 'requests' );
		$hostnames = \Shazzad\WpLogs\DbAdapter::get_col( "SELECT DISTINCT `request_hostname` FROM $table" );

		set_transient( $cache_key, $hostnames, 5 * MINUTE_IN_SECONDS );
	}

	return $hostnames;
}

function swpl_get_request_methods() {
	return [
		'GET'     => __( 'GET', 'swpl' ),
		'POST'    => __( 'POST', 'swpl' ),
		'PUT'     => __( 'PUT', 'swpl' ),
		'DELETE'  => __( 'DELETE', 'swpl' ),
		'HEAD'    => __( 'HEAD', 'swpl' ),
		'OPTIONS' => __( 'OPTIONS', 'swpl' )
	];
}

function swpl_clear_cache() {
	delete_transient( 'swpl_sources' );
}

function swpl_debug( $data, $die = false ) {
	echo '<pre>';
	print_r( $data );
	echo '</pre>';

	if ( $die ) {
		die();
	}
}

function swpl_log( $message, $context = [] ) {
	do_action( 'swpl_log', 'Log', $message, $context );
}