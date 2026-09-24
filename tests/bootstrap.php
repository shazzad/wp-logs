<?php
/**
 * PHPUnit bootstrap: loads the WP test suite with WP Logs active.
 *
 * @package Shazzad\WpLogs
 */

require_once dirname( __DIR__ ) . '/vendor-dev/autoload.php';

$swpl_tests_dir = getenv( 'WP_PHPUNIT__DIR' );
if ( ! $swpl_tests_dir ) {
	$swpl_tests_dir = dirname( __DIR__ ) . '/vendor-dev/wp-phpunit/wp-phpunit';
}

putenv( 'WP_PHPUNIT__TESTS_CONFIG=' . __DIR__ . '/wp-tests-config.php' );

require_once $swpl_tests_dir . '/includes/functions.php';

tests_add_filter(
	'muplugins_loaded',
	function () {
		require dirname( __DIR__ ) . '/shazzad-wp-logs.php';
	}
);

require $swpl_tests_dir . '/includes/bootstrap.php';
