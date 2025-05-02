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
	protected static $instance = null;

	/**
	 * Constructor.
	 */
	private function __construct() {
	}

	/**
	 * Class instance getter.
	 */
	public static function instance() {
		if ( self::$instance === null ) {
			self::$instance = new self();
			self::$instance->include_files();
			self::$instance->initialize();

			add_action( 'init', array( self::$instance, 'load_plugin_translations' ) );
			add_action( 'init', array( self::$instance, 'maybe_upgrade_db' ) );

			do_action( 'shazzad_wp_logs/loaded' );
		}

		return self::$instance;
	}

	/**
	 * Define constants.
	 */
	private function include_files() {
		require_once SWPL_DIR . 'vendor/autoload.php';
	}

	/*
	 * Boot plugin features.
	 */
	private function initialize() {
		// Load mustache, it is used for parsing message.
		\Mustache_Autoloader::register();

		// Filter/action hook callbacks.
		new Hooks();

		if ( is_admin() ) {
			// Admin interface.
			new Admin\Main();
		}
	}


	/**
	 * Load plugin translation file.
	 */
	public function load_plugin_translations() {
		load_plugin_textdomain(
			'shazzad-wp-logs',
			false,
			basename( dirname( HOMELOCAL_PLUGIN_FILE ) ) . '/languages'
		);
	}

	/**
	 * Upgrade/migrate database if required (on version change).
	 */
	public function maybe_upgrade_db() {
		if ( ! get_option( 'swpl_version' )
			|| version_compare( get_option( 'swpl_version' ), SWPL_VERSION, '!=' ) ) {

			Installer::upgrade();
		}
	}
}