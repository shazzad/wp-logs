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

function swpl_get_logable_requests() {
	return apply_filters( 'swpl_logable_requests', [] );
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