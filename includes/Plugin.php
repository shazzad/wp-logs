<?php
/**
 * Main Plugin Class
 * 
 * @package Shazzad\WpLogs
 */
namespace Shazzad\WpLogs;

/**
 * Plugin
 */
final class Plugin {

	/**
	 * @var object Plugin instance.
	 */
	protected static $_instance = null;

	/**
	 * Class instance getter.
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->define_constants();
		$this->initialize();

		do_action( 'shazzad_wp_logs/loaded' );
	}

	/*
	 * Define constants.
	 */
	private function define_constants() {
		define( 'SWPL_DIR', plugin_dir_path( SWPL_PLUGIN_FILE ) );
		define( 'SWPL_URL', plugin_dir_url( SWPL_PLUGIN_FILE ) );
		define( 'SWPL_BASENAME', plugin_basename( SWPL_PLUGIN_FILE ) );
	}

	/*
	 * Boot plugin features.
	 */
	private function initialize() {
		load_plugin_textdomain(
			'shazzad-wp-logs',
			false,
			basename( dirname( SWPL_PLUGIN_FILE ) ) . '/languages'
		 );

		// Load mustache, it is used for parsing message.
		\Mustache_Autoloader::register();

		// Filter/action hook callbacks.
		new Hooks();

		if ( is_admin() ) {
			// Admin interface.
			new Admin\Main();
		}
	}
}
