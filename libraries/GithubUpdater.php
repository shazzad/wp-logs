<?php
/**
 * WordPress Plugin Updater From Github 
 */
class GithubUpdater {

	/**
	 * @var string Plugin base file.
	 */
	private $file;

	/**
	 * @var array WordPress plugin data.
	 */
	private $plugin;

	/**
	 * @var string plugin basename.
	 */
	private $basename;

	/**
	 * @var boolean Is plugin active.
	 */
	private $active = false;

	/**
	 * @var boolean Is private repo.
	 */
	private $private_repo = true;

	/**
	 * @var string Github api access token.
	 */
	private $access_token;

	/**
	 * @var string Github repo path.
	 */
	private $repo_path;

	/**
	 * @var array Github latest release response.
	 */
	private $latest_release;

	/**
	 * @var number Cache period in seconds
	 */
	private $cache_period = 60;

	/**
	 * @var
	 */
	private $owner = 'triplerplugin';

	/**
	 * @var
	 */
	private $owner_name = 'Tripler Marketing';

	/**
	 * @var
	 */
	private $option_prefix = 'triplerplugin_';

	/**
	 * Bootstrap updater.
	 */
	public function __construct( $config = array() ) {
		if ( empty( $config['file'] ) ) {
			return;
		}

		// Very important.
		$this->file = $config['file'];

		// Old compat config property.
		if ( ! empty( $config['api_slug'] ) ) {
			$this->repo_path = $config['api_slug'];
		} else {
			$this->repo_path = $config['repo_path'];
		}

		// @TODO - rather than using repo path, we can use owner & repo config.
		// And then use the owner value as prefix. This will enable us to use the same key
		// for repositories under same owner. As an access can be used to access all repo
		// if same owner.
		// if ( empty( $config['option_prefix'] ) ) {
		// 	$this->option_prefix = current( explode( '/', $this->repo_path ) ) . '_';
		// }
		if ( ! empty( $config['owner'] ) ) {
			$this->owner = $config['owner'];
		}

		if ( ! empty( $config['owner_name'] ) ) {
			$this->owner_name = $config['owner_name'];
		}

		if ( ! empty( $config['option_prefix'] ) ) {
			$this->option_prefix = $config['option_prefix'];
		}

		// Old compat config property
		if ( ! empty( $config['access_token'] ) ) {
			$this->private_repo = true;
		} elseif ( array_key_exists( 'private_repo', $config ) ) {
			$this->private_repo = (bool) $config['private_repo'];
		}

		if ( get_option( $this->prefixed_option( 'github_access_token' ) ) ) {
			$this->access_token = get_option( $this->prefixed_option( 'github_access_token' ) );
		}

		// Initialize settings panel for private repo.
		if ( $this->private_repo ) {
			$this->initialize_settings();
		}

		// Initialize updater procedure.
		if ( $this->is_ready_to_user_updater() ) {
			$this->initialize_updater();
		}
	}

	/**
	 * Prefix option with owner/option_prefix
	 */
	private function prefixed_option( $option ) {
		return $this->option_prefix . $option;
	}

	/**
	 * Prefix option name with current plugin slug
	 */
	private function prefixed_plugin_option( $option ) {
		return $this->prefixed_option( "{$option}_" . str_replace( '/', '_', $this->repo_path ) );
	}

	/**
	 * Private repo will need 
	 */
	private function is_ready_to_user_updater() {
		if ( $this->private_repo && empty( $this->access_token ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Initialize settings option for private repo plugin.
	 */
	private function initialize_settings() {
		// Delete access token error after option is updated.
		add_action( 'update_option_' . $this->prefixed_option( 'github_access_token' ), array( $this, 'after_access_token_updated' ) );
		add_action( 'admin_init', array( $this , 'register_setting_field' ) );
		add_action( 'admin_notices', array( $this, 'access_token_admin_notices' ) );
	}

	/**
	 * After access token is updated, clear stored error & latest release transient data.
	 */
	public function after_access_token_updated() {
		$this->delete_access_token_error();
		$this->delete_latest_release_cache();
	}

	/**
	 * Display admin notice related to access token.
	 */
	public function access_token_admin_notices() {
		if ( ! $this->private_repo || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		static $notice_shown;
		if ( ! isset( $notice_shown ) ) {
			$notice_shown = array();
		}

		if ( is_array( $notice_shown ) && in_array( $this->owner, $notice_shown ) ) {
			return;
		}

		if ( empty( $this->access_token ) ) {
			echo "<div id='triplerplugin_missing_github_access_token' class='notice notice-error is-dismissible'> \n";
			echo "<p><strong>";
			printf( 
				__( '%s\'s <a href="%s#%s">github access token</a> is required to receive automatic plugin updates.' ), 
				$this->owner_name,
				admin_url( 'options-general.php' ),
				$this->prefixed_option( 'github_access_token_id' )
			);
			echo "</strong></p>";
			echo "</div> \n";

			$notice_shown[] = $this->owner;
		}

		if ( $this->get_access_token_error() ) {
			echo "<div id='". $this->prefixed_option( 'github_access_token' ) ."' class='notice notice-error is-dismissible'> \n";
			echo "<p>" . sprintf( 
				__( '<strong>%s\'s github access token error:</strong> %s <a href="%s#%s">update here</a>' ), 
				$this->owner_name,
				$this->get_access_token_error(),
				admin_url( 'options-general.php' ),
				$this->prefixed_option( 'github_access_token_id' )
			) . "</p>";
			echo "</div> \n";

			$notice_shown[] = $this->owner;
		}
	}

	private function get_access_token_error() {
		return get_option( $this->prefixed_option( 'github_access_token_error' ) );
	}

	private function save_access_token_error( $error ) {
		update_option( $this->prefixed_option( 'github_access_token_error' ), $error );
	}

	private function delete_access_token_error() {
		delete_option( $this->prefixed_option( 'github_access_token_error' ) );
	}

	private function get_latest_release_cache() {
		return get_transient( $this->prefixed_plugin_option( 'latest_release' ) );
	}

	private function save_latest_release_cache( $data ) {
		set_transient( $this->prefixed_plugin_option( 'latest_release' ), $data, $this->cache_period );
	}

	private function delete_latest_release_cache() {
		delete_transient( $this->prefixed_plugin_option( 'latest_release' ) );
	}

	/**
	 * Register settings field for access token.
	 */
	public function register_setting_field() {
		register_setting( 
			'general',
			$this->prefixed_option( 'github_access_token' ),
			'esc_attr'
		);

		add_settings_field(
			$this->prefixed_option( 'github_access_token_id' ),
			'<label for="'. $this->prefixed_option( 'github_access_token_id' ) .'">' . sprintf( __( '%s\'s Plugin Github Access Token' ), $this->owner_name ) . '</label>',
			array( $this, 'fields_html' ),
			'general'
		);

		global $pagenow;
		if ( 'options-general.php' === $pagenow && ! empty( $this->access_token ) ) {
			static $access_token_checked;
			if ( ! isset( $access_token_checked ) ) {
				$access_token_checked = array();
			}

			if ( is_array( $access_token_checked ) && in_array( $this->owner, $access_token_checked ) ) {
				return;
			}

			$access_token_checked[] = $this->owner;
			$this->fetch_latest_release();
		}
	}

	/**
	 * HTML for extra settings
	 */
	public function fields_html() {
		$value = get_option( $this->prefixed_option( 'github_access_token' ), '' );

		printf( 
			'<input class="regular-text" type="text" id="%s" name="%s" value="%s" />',
			$this->prefixed_option( 'github_access_token_id' ),
			$this->prefixed_option( 'github_access_token' ),
			esc_attr( $value )
		);

		echo '<p class="description">' . sprintf( 
			__( 'This token will be used to fetch update for %s\'s plugins from github' ),
			$this->owner
		) . '</p>';
	}

	private function initialize_updater() {
		add_action( 'admin_init', array( $this, 'set_plugin_properties' ) );
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'transient_update_plugins' ) );
		add_filter( 'plugins_api', array( $this, 'plugins_api_data' ), 10, 3 );
		add_filter( 'upgrader_post_install', array( $this, 'after_install' ), 10, 3 );
		add_filter( 'upgrader_pre_download', array( $this, 'upgrader_pre_download' ) );
	}

	public function upgrader_pre_download( $reply ) {
		// Add accept header to http request
		add_filter( 'http_request_args', array( $this, 'download_package' ), 15, 2 );

		return $reply;
	}

	public function set_plugin_properties() {
		$this->plugin	= get_plugin_data( $this->file );
		$this->basename = plugin_basename( $this->file );
		$this->active	= is_plugin_active( $this->basename );

		// echo '<pre>';
		// $this->fetch_latest_release();
		// print_r( $this->latest_release );
		// exit;
		// echo '<pre>';
		// print_r( $this->plugin );
		// exit;
	}

	private function fetch_latest_release() {
		if ( false !== $this->get_latest_release_cache() ) {
			$this->latest_release = $this->get_latest_release_cache();
			return;
		}

		if ( null === $this->latest_release ) {
	        $request_uri = sprintf( 'https://api.github.com/repos/%s/releases?per_page=5', $this->repo_path );

			$args = array();
	        if ( $this->access_token ) {
		          $args['headers']['Authorization'] = "token {$this->access_token}";
	        }

			$response      = wp_remote_get( $request_uri, $args );
			$body          = wp_remote_retrieve_body( $response );
			$data          = json_decode( $body, true );
			$response_code = wp_remote_retrieve_response_code( $response );
			$error         = '';

			if ( is_wp_error( $body ) ) {
				$error = $body;

			} elseif ( 200 !== $response_code ) {
				$error_code = 'api_error';

				if ( 401 === $response_code ) {
					$error_code = 'invalid_authentication';
					$error_message = 'Authentication error';
				} elseif ( isset( $data['message'] ) ) {
					$error_message = $data['message'];
				} else {
					$error_message = sprintf( 'Response code received: %d', $response_code );
				}

				$error = new WP_Error( $error_code, $error_message, array( 'code' => $response_code ) );
			}

			if ( ! empty( $error ) ) {

				$this->latest_release = false;

				$this->save_access_token_error(
					sprintf(
						'%s, code: %s, status code: %s.',
						$error->get_error_message(),
						$error->get_error_code(),
						$response_code
					)
				);

			} else {

				$this->latest_release = false;

				foreach ( $data as $release ) {
					if ( ! empty( $release['assets'] ) && count( $release['assets'] ) > 0 ) {
						$this->latest_release = $this->sanitize_release_data( $release );
						break;
					}
				}

				$this->save_latest_release_cache( $this->latest_release );
				$this->delete_access_token_error();
			}
	    }
	}

	private function sanitize_release_data( $release ) {
		unset( $release['author'] );
		foreach ( $release['assets'] as &$asset ) {
			unset( $asset['uploader'] );
		}

		return $release;
	}

	public function get_latest_version_number() {
		return $this->latest_release['tag_name'];
	}

	public function get_latest_version_date() {
		return $this->latest_release['published_at'];
	}

	public function get_latest_version_changelog() {
		return str_replace( "\r\n", '<br />', $this->latest_release['body'] );
	}

	public function get_latest_version_download_url() {
		if ( $this->access_token ) {
			return add_query_arg( 'access_token', $this->access_token, $this->latest_release['assets'][0]['url'] );
		}

		return $this->latest_release['assets'][0]['url'];
	}

	public function get_latest_version_download_count() {
		return $this->latest_release['assets'][0]['download_count'];
	}

	public function get_latest_version_requires() {
		if ( preg_match( '/Requires:\s([\d\.]+)/i', $this->latest_release['body'], $m ) ) {
			return $m['1'];
		}

		return '5.0';
	}

	public function get_latest_version_tested() {
		if ( preg_match( '/Tested up to:\s([\d\.]+)/i', $this->latest_release['body'], $m ) ) {
			return $m['1'];
		} elseif ( preg_match( '/Tested:\s([\d\.]+)/i', $this->latest_release['body'], $m ) ) {
			return $m['1'];
		}

		return '5.0';
	}

	public function get_latest_version_requires_php() {
		if ( preg_match( '/Requires Php:\s([\d\.]+)/i', $this->latest_release['body'], $m ) ) {
			return $m['1'];
		}

		return '5.6';
	}

	public function transient_update_plugins( $transient ) {
		if ( property_exists( $transient, 'checked') && ! empty( $transient->checked ) ) {
			$this->fetch_latest_release();

			if ( empty( $this->latest_release ) ) {
				return $transient;
			}

			if ( version_compare( $this->get_latest_version_number(), $this->plugin['Version'], 'gt' ) ) {
				$transient->response[ $this->basename ] = (object) $this->plugin_update_available_response_data();

			} else {
				// No update response is important to whitelist this plugin for automatic update.
				$transient->no_update[ $this->basename ] = (object) $this->plugin_no_update_response_data();
			}
		}

		return $transient;
	}

	public function plugins_api_data( $result, $action, $args ) {
		if ( ! empty( $args->slug ) ) {

			if ( $args->slug == current( explode( '/' , $this->basename ) ) ) {

				$this->fetch_latest_release();

				if ( empty( $this->latest_release ) ) {
					return $result;
				}

				return (object) $this->plugin_api_data();
			}
		}

		return $result;
	}

	private function plugin_no_update_response_data() {
		return array(
			'url'         => $this->plugin["PluginURI"],
			'slug' 	      => current( explode('/', $this->basename ) ),
			'package'     => $this->get_latest_version_download_url(),
			'new_version' => $this->plugin["Version"]
		);
	}

	private function plugin_update_available_response_data() {
		return array(
			'url'          => $this->plugin["PluginURI"],
			'slug' 	       => current( explode('/', $this->basename ) ),
			'package'      => $this->get_latest_version_download_url(),
			'new_version'  => $this->get_latest_version_number(),
			'requires'	   => $this->get_latest_version_requires(),
			'tested'	   => $this->get_latest_version_tested(),
			'requires_php' => $this->get_latest_version_requires_php()
		);
	}

	private function plugin_api_data() {
		return array(
			'name'				=> $this->plugin["Name"],
			'slug'				=> $this->basename,
			'requires'			=> $this->get_latest_version_requires(),
			'tested'			=> $this->get_latest_version_tested(),
			'requires_php'		=> $this->get_latest_version_requires_php(),
			'rating'			=> '',
			'num_ratings'		=> '',
			'downloaded'		=> $this->get_latest_version_download_count(),
			'version'			=> $this->get_latest_version_number(),
			'author'			=> sprintf( '<a href="%s">%s</a>', $this->plugin["AuthorURI"], $this->plugin["AuthorName"] ),
			'last_updated'		=> $this->get_latest_version_date(),
			'homepage'			=> $this->plugin["PluginURI"],
			'short_description' => $this->plugin["Description"],
			'sections'			=> array(
				'Description'	=> $this->plugin["Description"],
				'changelog'		=> $this->get_latest_version_changelog(),
			),
			'download_link'		=> $this->get_latest_version_download_url()
		);
	}

	public function download_package( $args, $url ) {
		if ( null !== $args['filename'] ) {
			$args = array_merge( $args, array( "headers" => array( "Accept" => "application/octet-stream" ) ) );
		}

		remove_filter( 'http_request_args', array( $this, 'download_package' ), 15, 2 );

		return $args;
	}

	/**
	 * Move plugin files to desired location, and activate if it was active earlier.
	 */
	public function after_install( $response, $hook_extra, $result ) {
		global $wp_filesystem;

		$install_directory = plugin_dir_path( $this->file );
		$wp_filesystem->move( $result['destination'], $install_directory );
		$result['destination'] = $install_directory;

		if ( $this->active ) {
			activate_plugin( $this->basename );
		}

		return $result;
	}
}
