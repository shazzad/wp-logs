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
		'debug'     => __( 'Debug', 'shazzad-wp-logs' ),
		'info'      => __( 'Info', 'shazzad-wp-logs' ),
		'notice'    => __( 'Notice', 'shazzad-wp-logs' ),
		'warning'   => __( 'Warning', 'shazzad-wp-logs' ),
		'error'     => __( 'Error', 'shazzad-wp-logs' ),
		'critical'  => __( 'Critical', 'shazzad-wp-logs' ),
		'emergency' => __( 'Emergency', 'shazzad-wp-logs' )
	];
}

function swpl_get_request_hostnames() {
	$cache_key = 'swpl_request_hostnames';

	$hostnames = get_transient( $cache_key );

	if ( false === $hostnames ) {
		$table     = \Shazzad\WpLogs\DbAdapter::prefix_table( 'requests' );
		$hostnames = \Shazzad\WpLogs\DbAdapter::get_col( "SELECT DISTINCT `hostname` FROM $table" );

		set_transient( $cache_key, $hostnames, 5 * MINUTE_IN_SECONDS );
	}

	return $hostnames;
}

function swpl_get_request_methods() {
	return [
		'GET'     => __( 'GET', 'shazzad-wp-logs' ),
		'POST'    => __( 'POST', 'shazzad-wp-logs' ),
		'PUT'     => __( 'PUT', 'shazzad-wp-logs' ),
		'DELETE'  => __( 'DELETE', 'shazzad-wp-logs' ),
		'HEAD'    => __( 'HEAD', 'shazzad-wp-logs' ),
		'OPTIONS' => __( 'OPTIONS', 'shazzad-wp-logs' )
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