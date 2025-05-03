<?php
namespace Shazzad\WpLogs\Admin\Requests;

use Shazzad\WpLogs\Utils;
use Shazzad\WpLogs\Request;

require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';

class ListTable extends \WP_List_Table {

	# Construct
	public function __construct() {
		parent::__construct( [
			'singular' => 'swpl-log',
			'plural'   => 'swpl-logs',
			'screen'   => get_current_screen()->id,
			'ajax'     => false
		] );
	}

	# Columns
	public function get_columns() {
		$columns = [
			'cb'            => '<input type="checkbox" id="cb-select-all-1">',
			'title'         => __( 'URL', 'shazzad-wp-logs' ),
			'method'        => __( 'Method', 'shazzad-wp-logs' ),
			'response_code' => __( 'Status', 'shazzad-wp-logs' ),
			'response_size' => __( 'Size', 'shazzad-wp-logs' ),
			'date'          => __( 'Date (UTC)', 'shazzad-wp-logs' ),
			'id'            => __( 'ID', 'shazzad-wp-logs' )
		];

		foreach ( $this->get_queryable_columns() as $qr => $qc ) {
			if ( isset( $_GET[$qr] ) && ! empty( $_GET[$qr] ) && $_GET[$qr] != '-1' && isset( $columns[$qc[0]] ) )
				unset( $columns[$qc[0]] );
		}

		$columns = apply_filters( 'manage_swpl_columns', $columns );

		return $columns;
	}

	# Queryable Columns
	public function get_queryable_columns() {
		return [
			's'    => [ 'search', __( 'Search Result: ', 'shazzad-wp-logs' ) ],
			'date' => [ 'timestamp', __( 'Date: ', 'shazzad-wp-logs' ) ],
		];
	}

	# Sortable Columns
	public function get_sortable_columns() {
		return [
			'id'   => [ 'id' ],
			'date' => [ 'timestamp' ]
		];
	}

	# Prepare / init
	public function prepare_items() {
		$this->register_screen_options();

		$per_page = get_user_option( get_current_screen()->get_option( 'per_page', 'option' ) );
		if ( $per_page < 1 ) {
			$per_page = 10;
		}

		$query_args = [
			'limit'   => $per_page,
			'paged'   => ( isset( $_GET['paged'] ) && $_GET['paged'] > 1 ) ? $_GET['paged'] : 1,
			'orderby' => ( isset( $_GET['orderby'] ) && $_GET['orderby'] ) ? $_GET['orderby'] : 'id',
			'order'   => ( isset( $_GET['order'] ) && '-1' != $_GET['order'] ) ? $_GET['order'] : 'DESC'
		];

		foreach ( array_keys( $this->get_queryable_columns() ) as $qr ) {
			if ( isset( $_GET[$qr] ) && '' != $_GET[$qr] && $_GET[$qr] != '-1' ) {
				$query_args[$qr] = urldecode( $_GET[$qr] );
			}
		}

		$query_args = stripslashes_deep( $query_args );

		$query = new Request\Query( $query_args );
		$query->query();

		$this->items = $query->get_objects();

		$this->set_pagination_args( [
			'total_items' => (int) $query->found_items,
			'total_pages' => (int) $query->max_num_pages,
			'per_page'    => (int) $per_page,
		] );
	}

	# prepare / init
	public function register_screen_options() {
		$option = 'per_page';
		$args   = [
			'label'   => __( 'Number of items per page:' ),
			'default' => 10,
			'option'  => 'swpl_logs_per_page'
		];

		add_screen_option( $option, $args );
	}

	/**
	 * @param Request\Data $request
	 */
	public function single_row( $request ) {
		$classes = 'swpl-request';
		?>
		<tr id="swpl-log-<?php echo $request->get_id(); ?>" class="<?php echo $classes; ?>">
			<?php $this->single_row_columns( $request ); ?>
		</tr>
		<?php
	}

	public function display_tablenav( $which ) {
		?>
		<div class="tablenav <?php echo esc_attr( $which ); ?>">
			<?php if ( 'top' == $which ) { ?>
				<div class="alignleft actions bulkactions">
					<select name="action">
						<option selected="selected" value="-1"><?php _e( 'Bulk actions', 'shazzad-wp-logs' ); ?></option>
						<option value="bulk_delete"><?php _e( 'Delete', 'shazzad-wp-logs' ); ?></option>
					</select>
					<input type="submit" value="<?php
					_e( 'Apply', 'shazzad-wp-logs' ); ?>" class="button action" id="doaction" name="" />
				</div>

			<?php } ?>
			<?php $this->pagination( $which ); ?>
			<br class="clear" />
		</div>
		<?php
	}

	public function get_views() {
		$base_url = remove_query_arg(
			[
				'action',
				'filter_action',
				'paged',
				'id',
				'ok',
				'message',
				'error',
				's',
			]
		);

		$view_active = false;
		$links       = [
			[
				'type'  => 'all',
				'count' => '',
				'name'  => __( 'All', 'shazzad-wp-logs' ),
				'url'   => $base_url,
				'class' => ''
			]
		];

		foreach ( $this->get_queryable_columns() as $qr => $qc ) {
			if ( isset( $_GET[$qr] ) && ! empty( $_GET[$qr] ) && $_GET[$qr] != '-1' ) {
				$view_active = true;
				$column      = $qc[0];
				$name        = $qc[1];
				$value       = urlencode( $_GET[$qr] );

				$count = urldecode( $_GET[$qr] );

				$links[] = [
					'type'  => $qr,
					'count' => $count,
					'name'  => $name,
					'url'   => add_query_arg( $qr, $value, $base_url ),
					'class' => 'active'
				];
			}
		}

		if ( ! $view_active ) {
			$links['0']['class'] = 'current';
		} else {
			$links['0']['name'] = __( 'All Logs', 'shazzad-wp-logs' );
		}

		$_links = [];
		foreach ( $links as $link ) {
			$type     = $link['type'];
			$type_txt = '';

			if ( ! empty( $link['count'] ) ) {
				$type_txt = sprintf( ' <span class="count">(%s)</span>', $link['count'] );
			}

			$_links[$type] = sprintf(
				'<a href="%1$s" class="%2$s" title="%3$s">%3$s %4$s</a>',
				$link['url'],
				$link['class'],
				$link['name'],
				$type_txt
			);
		}

		return $_links;
	}

	/**
	 * Checkbox column
	 */
	public function column_cb( $request ) {
		printf( '<input id="cb-select-%1$d" type="checkbox" name="ids[]" value="%1$d" />', $request->get_id() );
	}

	public function column_default( $request, $column ) {
		do_action( 'manage_swpl_custom_column', $column, $request->get_id() );
	}

	public function column_title( $request ) {
		echo $request->get_request_url();

		$actions = [];

		$actions['delete'] = '<a href="' . add_query_arg( [ 'action' => 'delete', 'id' => $request->get_id() ] ) . '">Delete</a>';
		$actions['view']   = '<a href="' . add_query_arg( [ 'action' => 'view', 'id' => $request->get_id() ] ) . '">View</a>';

		echo $this->row_actions( $actions );

		?>
		<div id="swpl-log-modal-<?php echo $request->get_id(); ?>" style="display:none;">
			<div class="swpl-modal-header">
				<?php echo $request->get_request_url(); ?>
			</div>

			<div class="swpl-modal-content">
				<?php
				if ( $request->get_request_payload() ) {
					echo '<pre>';
					print_r( $request->get_request_payload() );
					echo '</pre>';
				}
				?>
			</div>
			<div class="swpl-modal-footer"><strong class="swpl-id"># <?php echo $request->get_id(); ?></strong></div>
		</div>
		<?php
	}

	public function column_id( $request ) {
		echo $request->get_id();
	}

	public function column_method( $request ) {
		echo esc_html( $request->get_request_method() );
	}

	public function column_response_code( $request ) {
		echo esc_html( $request->get_response_code() );
	}

	public function column_response_size( $request ) {
		echo esc_html( $request->get_response_size() );
	}

	public function column_date( $request ) {
		if ( ! $request->get_timestamp() ) {
			_e( 'N/A' );
		} else {
			printf(
				'%s<br/><small style="color:#999;">%s</small>',
				mysql2date( 'h:i A', $request->get_timestamp() ),
				mysql2date( 'd M Y', $request->get_timestamp() )
			);
		}
	}

	/**
	 * No items
	 */
	public function no_items() {
		_e( 'No logs', 'shazzad-wp-logs' );
	}

	/**
	 * Search box
	 */
	public function search_box( $text, $input_id ) {
		?>
		<p class="search-box">
			<label for="<?php echo $input_id; ?>" class="screen-reader-text"><?php echo $text; ?></label>
			<input type="text" name="s" id="<?php echo $input_id ?>" value="<?php _admin_search_query(); ?>"
				placeholder="<?php esc_attr_e( 'Message..', 'shazzad-wp-logs' ); ?>"
				title="<?php esc_attr_e( 'Search by message', 'shazzad-wp-logs' ); ?>" />
			<?php submit_button( $text, 'button', false, false, [ 'id' => 'search-submit' ] ); ?>
		</p>
		<?php
	}
}
