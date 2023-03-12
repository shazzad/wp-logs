<?php
/**
 * Register CSS/JS
 * 
 * @package Shazzad\WpLogs
 */

namespace Shazzad\WpLogs\Admin;

use Shazzad\WpLogs\Log\Data as LogData;
use Shazzad\WpLogs\Log\Query as LogQuery;
use Shazzad\WpLogs\Log\Api as LogApi;

class AjaxHandler
{
	public function __construct()
	{
		add_action('wp_ajax_swpl_delete_logs', array($this, 'delete_logs_ajax'));
		add_action('wp_ajax_swpl_delete_log', array($this, 'delete_log_ajax'));
	}

	public function delete_logs_ajax()
	{
		if (!current_user_can('manage_options')) {
			wp_send_json_error(
				array(
					'message' => __('Unauthorized Request')
				)
			);
		}

		$args = array();
		if (!empty($_REQUEST['args'])) {
			$args = $_REQUEST['args'];
		}

		$api = new LogApi();
		$return = $api->delete_all($args);

		if (is_wp_error($return)) {
			wp_send_json_error(
				array(
					'message' => $return->get_error_message()
				)
			);
		}

		wp_send_json_success($return);
	}

	public function delete_log_ajax()
	{
		if (!current_user_can('manage_options')) {
			wp_send_json_error(
				array(
					'message' => __('Unauthorized Request')
				)
			);
		}

		if (empty($_REQUEST['id'])) {
			wp_send_json_error(
				array(
					'message' => __('Empty log id')
				)
			);
		}

		$log = new LogData(intval($_REQUEST['id']));
		if ($log->get_id()) {
			$log->delete();
		}

		wp_send_json_success(
			array(
				'message' => __('Log deleted')
			)
		);
	}
}