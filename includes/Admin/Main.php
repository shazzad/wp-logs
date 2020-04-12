<?php
namespace W4dev\Loggable\Admin;

/**
 * Admin Environment
 * @package WordPress
 * @subpackage SERVED Admin
 * @author Shazzad Hossain Khan
 * @url https://shazzad.me
**/


class Main
{
	function __construct()
	{
		add_filter( 'set-screen-option'											, array( $this, 'set_screen_option' ), 10, 3 );
		add_filter( 'plugin_action_links_' . W4_LOGGABLE_BASENAME			  	, array( $this, 'plugin_action_links' ) );
		add_action( 'W4_loggable/admin_page/notices'							, array( $this, 'admin_page_notices' ) );
	}

	public function clear_cache_ajax()
	{
		if ( ! current_user_can( 'administrator' ) ) {
			wp_send_json( [
				'success' => false,
				'message' => __( 'Sorry, you cant do this.', 'w4-loggable' )
			] );
		}

		global $wpdb;
		$match = '%\_W4_loggable\_%';
		$options = $wpdb->get_col( "SELECT option_name FROM $wpdb->options WHERE option_name LIKE '$match'" );
		if ( ! empty( $options ) ) {
			foreach( $options as $option ) {
				delete_option( $option );
			}
		}

		// clear orphan postmeta
		$wpdb->query( "DELETE pm FROM wp_postmeta pm LEFT JOIN wp_posts wp ON wp.ID = pm.post_id WHERE wp.ID IS NULL" );

		// clear opcache
		if ( function_exists( 'opcache_reset' ) ) {
			opcache_reset();
		}

		wp_send_json( [
			'success' => true,
			'message' => __( 'Cache cleaned.', 'w4-loggable' )
		] );
	}

	public function set_screen_option( $status, $option, $value )
	{
		if ( ! empty( $option ) && 'W4_loggable' == substr( $option, 0, 7 ) ) {
			return $value;
		}

		return $status;
	}

	public function plugin_action_links( $links )
	{
		$links['logs'] = '<a href="'. admin_url( 'tools.php?page=w4-loggable' ) .'">' . __( 'W4 Logs', 'w4-loggable' ). '</a>';
		return $links;
	}

	public function admin_page_notices()
	{
		?><div id="w4_loggable_admin_notes">
			<?php if(  isset( $_GET['error'] ) && !empty( $_GET['error'] )  ){ ?><div class="_error"><p><?php
				echo stripslashes( urldecode( $_GET['error'] ) ); ?></p></div><?php
			} ?>
			<?php if(  isset( $_GET['ok'] ) && !empty( $_GET['ok'] )  ){ ?><div class="_ok"><p><?php
				echo stripslashes( urldecode( $_GET['ok'] ) ); ?></p></div><?php
			} ?>
			<?php if(  isset( $_GET['message'] ) && !empty( $_GET['message'] )  ){ ?><div class="_ok"><p><?php
				echo stripslashes( urldecode( $_GET['message'] ) ); ?></p></div><?php
			} ?>
		</div><?php
	}
}
