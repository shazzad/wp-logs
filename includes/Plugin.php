<?php
/**
 * Main Plugin Class
 * @package W4dev\Loggable
 */

namespace W4dev\Loggable;

final class Plugin
{
	// plugin name
	public $name = 'W4 Loggable';

	// plugin version
	public $version = '1.0.2';

	// class instance holder
	protected static $_instance = null;

	/**
	 * Class instance getter
	 */
	public static function instance()
	{
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Constructor
	 */
	private function __construct()
	{
		$this->define_constants();
		$this->initialize();

		do_action( 'w4_loggable/loaded' );
	}

	/*
	 * Define constants
	 */
	private function define_constants()
	{
		define( 'W4_LOGGABLE_NAME', $this->name );
		define( 'W4_LOGGABLE_VERSION', $this->version );
		define( 'W4_LOGGABLE_DIR', plugin_dir_path( W4_LOGS_PLUGIN_FILE ) );
		define( 'W4_LOGGABLE_URL', plugin_dir_url( W4_LOGS_PLUGIN_FILE ) );
		define( 'W4_LOGGABLE_BASENAME', plugin_basename( W4_LOGS_PLUGIN_FILE ) );
	}

	/*
	 * Bootstrap plugin
	 */
	private function initialize()
	{
		load_plugin_textdomain(
			'w4-loggable',
			false,
			basename( dirname( W4_LOGS_PLUGIN_FILE ) ) . '/languages'
		 );

		// mustache used for parsing message
		\Mustache_Autoloader::register();

		new Hooks();

		if ( is_admin() ) {
			new Admin\Main();
		}
	}
}
