<?php
/**
 * Register CSS/JS
 * 
 * @package Shazzad\WpLogs
 */

namespace Shazzad\WpLogs\Admin;

class Notices {
	public function __construct() {
		add_action( 'shazzad_wp_logs/admin_page/notices', array( $this, 'admin_page_notices' ) );
	}

	public function admin_page_notices() {
		?>
		<div id="swpl_admin_notes">
			<?php if ( isset( $_GET['error'] ) && ! empty( $_GET['error'] ) ) { ?>
				<div class="_error">
					<p>
						<?php echo stripslashes( urldecode( $_GET['error'] ) ); ?>
					</p>
				</div>
			<?php } ?>
			<?php if ( isset( $_GET['ok'] ) && ! empty( $_GET['ok'] ) ) { ?>
				<div class="_ok">
					<p>
						<?php echo stripslashes( urldecode( $_GET['ok'] ) ); ?>
					</p>
				</div>
			<?php } ?>
			<?php if ( isset( $_GET['message'] ) && ! empty( $_GET['message'] ) ) { ?>
				<div class="_ok">
					<p>
						<?php echo stripslashes( urldecode( $_GET['message'] ) ); ?>
					</p>
				</div>
			<?php } ?>
		</div>
		<?php
	}
}
