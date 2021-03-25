<?php

class GithubUpdater {

	private $file;
	private $plugin;
	private $basename;
	private $active;
	private $api_slug;
	private $api_errors = false;
	private $date_added = '2021-01-01';
	private $authorize_token;
	private $github_latest_release;

	public function __construct( $config = array() ) {
		$this->setup( $config );
		$this->initialize();
	}

	protected function setup( $config ) {
		$this->file = $config['file'];
		$this->api_slug = $config['api_slug'];

		if ( ! empty( $config['access_token'] ) ) {
			$this->authorize_token = $config['access_token'];
		}

		if ( ! empty( $config['date_added'] ) ) {
			$this->date_added = $config['date_added'];
		}
	}

	public function initialize() {
		add_action( 'admin_init', array( $this, 'set_plugin_properties' ) );
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'transient_update_plugins' ) );
		add_filter( 'plugins_api', array( $this, 'plugins_api_data' ), 10, 3 );
		add_filter( 'upgrader_post_install', array( $this, 'after_install' ), 10, 3 );
		add_filter( 'upgrader_pre_download', array( $this, 'upgrader_pre_download' ) );
	}

	public function upgrader_pre_download( $reply ) {
		// Add Authorization Token to download_package
		add_filter( 'http_request_args', array( $this, 'download_package' ), 15, 2 );

		return $reply;
	}

	public function set_plugin_properties() {
		$this->plugin	= get_plugin_data( $this->file );
		$this->basename = plugin_basename( $this->file );
		$this->active	= is_plugin_active( $this->basename );

		// $this->fetch_latest_release();
		// print_r( $this->github_latest_release );
		// exit;
	}

	private function fetch_latest_release() {
	    if ( is_null( $this->github_latest_release ) ) {
	        $request_uri = sprintf( 'https://api.github.com/repos/%s/releases?per_page=1', $this->api_slug );

			$args = array();
	        if ( $this->authorize_token ) {
		          $args['headers']['Authorization'] = "token {$this->authorize_token}";
	        }
			
			$response = wp_remote_retrieve_body( wp_remote_get( $request_uri, $args ) );

			if ( is_wp_error( $response ) ) {
				$this->api_errors = $response;
				$this->github_latest_release = false;
				return;
			}

	        $repos = json_decode( $response, true );
			$latest_release = current( $repos );

	        $this->github_latest_release = $latest_release;
	    }
	}

	public function get_latest_version_number() {
		if ( ! $this->github_latest_release ) {
			return null;
		}

		return $this->github_latest_release['tag_name'];
	}

	public function get_latest_version_date() {
		if ( ! $this->github_latest_release ) {
			return null;
		}

		return $this->github_latest_release['published_at'];
	}

	public function get_latest_version_description() {
		if ( ! $this->github_latest_release ) {
			return null;
		}

		return $this->github_latest_release['body'];
	}

	public function get_latest_version_download_url() {
		if ( ! $this->github_latest_release ) {
			return null;
		}

		return $this->github_latest_release['assets'][0]['browser_download_url'];
	}

	public function get_latest_version_download_count() {
		if ( ! $this->github_latest_release ) {
			return null;
		}

		return $this->github_latest_release['assets'][0]['download_count'];
	}

	public function get_latest_version_requires() {
		if ( ! $this->github_latest_release ) {
			return null;
		}

		return '5.0';
	}

	public function get_latest_version_tested() {
		if ( ! $this->github_latest_release ) {
			return null;
		}

		return '5.7';
	}

	public function transient_update_plugins( $transient ) {
		if ( property_exists( $transient, 'checked') ) {

			if ( $checked = $transient->checked ) {

				$this->fetch_latest_release();

				if ( $this->api_errors && is_wp_error( $this->api_errors ) ) {
					return $transient;
				}

				if ( version_compare( $this->get_latest_version_number(), $checked[ $this->basename ], 'gt' ) ) {
					$slug = current( explode('/', $this->basename ) );

					$plugin = array(
						'url' 		  => $this->plugin["PluginURI"],
						'slug' 		  => current( explode('/', $this->basename ) ),
						'package' 	  => $this->get_latest_version_download_url(),
						'new_version' => $this->get_latest_version_number()
					);

					$transient->response[ $this->basename ] = (object) $plugin;
				}
			}
		}

		return $transient;
	}

	public function plugins_api_data( $result, $action, $args ) {
		if ( ! empty( $args->slug ) ) {

			if ( $args->slug == current( explode( '/' , $this->basename ) ) ) {

				$this->fetch_latest_release();

				if ( $this->api_errors && is_wp_error( $this->api_errors ) ) {
					return $result;
				}

				return (object) array(
					'name'				=> $this->plugin["Name"],
					'slug'				=> $this->basename,
					'requires'			=> $this->get_latest_version_requires(),
					'tested'			=> $this->get_latest_version_tested(),
					'rating'			=> '100.0',
					'num_ratings'		=> '0',
					'downloaded'		=> $this->get_latest_version_download_count(),
					'added'				=> $this->date_added,
					'version'			=> $this->get_latest_version_number(),
					'author'			=> $this->plugin["AuthorName"],
					'author_profile'	=> $this->plugin["AuthorURI"],
					'last_updated'		=> $this->get_latest_version_date(),
					'homepage'			=> $this->plugin["PluginURI"],
					'short_description' => $this->plugin["Description"],
					'sections'			=> array(
						'Description'	=> $this->plugin["Description"],
						'Updates'		=> $this->get_latest_version_description(),
					),
					'download_link'		=> $this->get_latest_version_download_url()
				);
			}
		}

		return $result;
	}
	
	public function download_package( $args, $url ) {
		if ( null !== $args['filename'] ) {
			if ( $this->authorize_token ) {
				$args = array_merge( $args, array( "headers" => array( "Authorization" => "token {$this->authorize_token}" ) ) );
			}
		}

		remove_filter( 'http_request_args', array( $this, 'download_package' ), 15, 2 );

		return $args;
	}

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
