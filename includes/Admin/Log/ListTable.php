<?php
namespace W4dev\Loggable\Admin\Log;

use W4dev\Loggable\Log\Data as LogData;
use W4dev\Loggable\Log\Query as LogQuery;

if (! class_exists('WP_List_Table')) {
	require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class ListTable extends \WP_List_Table {

	# Construct
	function __construct() {
		 parent::__construct([
			'singular'	=> 'w4_log',
			'plural' 	  => 'W4_loggable',
			'screen'	  => get_current_screen()->id,
			'ajax'		  => false
		]);
	}

	# Columns
	function get_columns() {
		$columns = [
			'cb' 					=> '<input type="checkbox" id="cb-select-all-1">',
			'title' 				=> __('ID', 'w4-loggable'),
			'level' 				=> __('Level', 'w4-loggable'),
			'message' 				=> __('Message', 'w4-loggable'),
			'source'	  			=> __('Source', 'w4-loggable'),
			'timestamp' 			=> __('Date', 'w4-loggable')
		];

		foreach ($this->get_queryable_columns() as $qr => $qc) {
			if (isset($_GET[$qr]) && !empty($_GET[$qr]) && $_GET[$qr] != '-1' && isset($columns[$qc[0]]))
				unset($columns[$qc[0]]);
		}

		$columns = apply_filters('manage_W4_Loggable_columns', $columns);

		return $columns;
	}

	# Queryable Columns
	function get_queryable_columns() {
		return [
			's' 						=> array('search'	, __('Search Result: ', 'w4-loggable')),
			'level'				 		=> array('level'	, __('Level: ', 'w4-loggable')),
			'timestamp'				 	=> array('timestamp', __('Date: ', 'w4-loggable')),
			'source'		 			=> array('source'	, __('Source: ', 'w4-loggable'))
		];
	}

	# Sortable Columns
	function get_sortable_columns() {
		return [
			'title'						=> array('id'),
			'level'						=> array('level'),
			'source'					=> array('source'),
			'time'						=> array('timestamp')
		];
	}


	# Prepare / init
	function prepare_items() {
		$this->register_screen_options();

		list ($columns, $hidden, $sortable) = $this->get_column_info();

		$per_page = get_user_option(get_current_screen()->get_option('per_page','option'));
		if ($per_page < 1) $per_page = 10;

		$query_args = [
			'limit' 			   => $per_page,
			'paged' 			   => (isset($_GET['paged']) && $_GET['paged'] > 1) ? $_GET['paged'] : 1,
			'orderby' 			   => (isset($_GET['orderby']) && $_GET['orderby']) ? $_GET['orderby'] : 'timestamp',
			'order' 			   => (isset($_GET['order']) && '-1' != $_GET['order']) ? $_GET['order'] : 'DESC'
		];

		foreach (array_keys($this->get_queryable_columns()) as $qr) {
			if (isset($_GET[$qr]) && '' != $_GET[$qr] && $_GET[$qr] != '-1') {
				$query_args[$qr] = urldecode($_GET[$qr]);
			}
		}

		#\W4dev\Loggable\Utils::d($query_args);

		$query_args = stripslashes_deep($query_args);
		$query = new LogQuery($query_args);
		$query->query();

		$resuts = $query->get_results();
		foreach ($resuts as $resut) {
		  $this->items[] = LogData::load($resut);
		}
		# \W4dev\Loggable\Utils::d($this->items);

		$this->set_pagination_args(array(
			'total_items' 	=> (int) $query->found_items,
			'total_pages' 	=> (int) $query->max_num_pages,
			'per_page' 		=> (int) $per_page,
		));
	}

	# prepare / init
	function register_screen_options() {
		$option = 'per_page';
		$args = array(
			'label' 	=> __('Number of items per page:'),
			'default' 	=> 10,
			'option' 	=> 'W4_Loggable_per_page'
		);

		add_screen_option($option, $args);
	}

	function display_tablenav($which) {
		?><div class="tablenav <?php echo esc_attr($which); ?>"><?php
		if ('top' == $which) {
			?>
			<div class="alignleft actions bulkactions">
				<select name="action">
					<option selected="selected" value="-1"><?php _e('Bulk Actions', 'w4-loggable'); ?></option>
					<option value="bulk_delete"><?php _e('Delete', 'w4-loggable'); ?></option>
				</select>
				<input type="submit" value="<?php _e('Apply', 'w4-loggable'); ?>" class="button action" id="doaction" name="">
	  		</div><?php
		}
		$this->pagination($which); ?>
		<br class="clear" />
		</div><?php
	}

	function get_views() {
		$base_url = remove_query_arg(array('action', 'filter_action', 'paged', 'id', 'ok', 'error', 's', 'status', 'step', 'customer_id', 'level', 'subscription_status'));

		$view_active = false;
		$links = array(
			array(
				'type' => 'all',
				'count' => '',
				'name' 	=> __('All', 'w4-loggable'),
				'url'	=> $base_url,
				'class'	=> ''
			)
		);

		foreach ($this->get_queryable_columns() as $qr => $qc) {
			if (isset($_GET[$qr]) && !empty($_GET[$qr]) && $_GET[$qr] != '-1') {
				$view_active = true;
				$column = $qc[0];
				$name = $qc[1];
				$value = urlencode($_GET[$qr]);

				#echo $qr; die();

				$count = urldecode($_GET[$qr]);

				$links[] = array(
					'type' 		=> $qr,
					'count' 	=> $count,
					'name' 		=> $name,
					'url'		=> add_query_arg($qr, $value, $base_url),
					'class'		=> 'active'
				);
			}
		}

		if (! $view_active) {
			$links['0']['class'] = 'current';
		} else {
			$links['0']['name'] = __('All Logs', 'w4-loggable');
		}

		$_links = array();
		foreach ($links as $link) {
			$type = $link['type'];
			$type_txt = '';

			if (!empty($link['count']))
				$type_txt = ' <span class="count">('. $link['count'] .')</span>';

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

	# === Columns ======
	function column_cb($log) {
		printf('<input id="cb-select-%1$d" type="checkbox" name="ids[]" value="%1$d" />', $log->get_id());
	}
	function column_default($log, $column) {
		do_action("manage_W4_Loggable_custom_column", $column, $log->get_id());
	}
	function column_title($log) {
		printf(
			'<strong><a class="row-title" href="%s">%d</a></strong>',
			admin_url('admin.php?page=w4-loggable-logs&action=view&id='. $log->get_id()),
			$log->get_id()
		);

		$actions = array();
		$actions['delete'] = '<a href="'. add_query_arg(['action' => 'delete', 'id' => $log->get_id()]) .'">Delete</a>';

		echo $this->row_actions($actions);
	}

	function column_level($log) {
		echo $log->get_level();
	}
	function column_source($log) {
		echo $log->get_source();
	}
	function column_message($log) {
		echo apply_filters('w4_loggable_format_message', $log->get_message(), $log->get_context());
	}
	function column_timestamp($log) {
		if (! $log->get_timestamp()) {
			_e('N/A');
		} else {
			printf('%s<br/>@ %s', mysql2date('d M Y', $log->get_timestamp()), mysql2date('h:i A', $log->get_timestamp()));
		}
	}
	# No items
	function no_items() {
		_e('No logs', 'w4-loggable');
	}

	# Search box
	function search_box($text, $input_id) {
		?><p class="search-box">
			<label for="<?php echo $input_id; ?>" class="screen-reader-text"><?php echo $text; ?></label>
			<input type="text" name="s" id="<?php echo $input_id ?>" value="<?php _admin_search_query(); ?>" placeholder="<?php _e('message..', 'w4-loggable'); ?>" title="<?php _e('Search by message', 'w4-loggable'); ?>" />
			<?php submit_button($text, 'button', false, false, array('id' => 'search-submit')); ?>
		</p>
		<?php
	}
}
