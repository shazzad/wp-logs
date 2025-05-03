<?php
/**
 * Main Plugin Class
 * 
 * @package Shazzad\WpLogs
 */
namespace Shazzad\WpLogs;

/**
 * Main plugin class for Shazzad WP Logs.
 *
 * This singleton class is responsible for initializing the plugin,
 * loading dependencies, and setting up core functionality.
 *
 * @since 1.0.0
 * @package Shazzad\WpLogs
 */
final class Plugin {
	/**
	 * Plugin instance.
	 *
	 * @since 1.0.0
	 * @var Plugin|null Single instance of the plugin.
	 */
	protected static $instance = null;

	/**
	 * Constructor.
	 *
	 * Private constructor to prevent direct instantiation.
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
	}

	/**
	 * Class instance getter.
	 *
	 * Ensures only one instance of the plugin is loaded.
	 *
	 * @since 1.0.0
	 * @return Plugin Single plugin instance.
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
	 * Include required files.
	 *
	 * Loads the autoloader and functions file.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function include_files() {
		require_once SWPL_DIR . 'vendor/autoload.php';

		require_once SWPL_DIR . 'includes/functions.php';
	}

	/**
	 * Initialize plugin components.
	 *
	 * Sets up Mustache templating and initializes all plugin components.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function initialize() {
		// Load mustache, it is used for parsing message.
		\Mustache_Autoloader::register();

		Cleanup::setup();
		RestApi::setup();
		Assets::setup();

		Hooks::setup();

		AdminBarMenu::setup();

		if ( is_admin() ) {
			Admin\Main::setup();
		}
	}


	/**
	 * Load plugin translation file.
	 *
	 * Loads the plugin's text domain for internationalization.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function load_plugin_translations() {
		load_plugin_textdomain(
			'shazzad-wp-logs',
			false,
			basename( dirname( HOMELOCAL_PLUGIN_FILE ) ) . '/languages'
		);
	}

	/**
	 * Upgrade/migrate database if required.
	 *
	 * Checks if the plugin version has changed and runs upgrade routines if needed.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function maybe_upgrade_db() {
		if ( ! get_option( 'swpl_version' )
			|| version_compare( get_option( 'swpl_version' ), SWPL_VERSION, '!=' ) ) {

			Installer::upgrade();
		}
	}
}