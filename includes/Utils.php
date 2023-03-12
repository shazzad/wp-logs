<?php
/**
 * Utility Class
 * 
 * @package Shazzad\WpLogs
 */

namespace Shazzad\WpLogs;

/**
 * Class Utils.
 */
class Utils
{
	public static function get_menu_items()
	{
		return apply_filters('swpl_menu_items', array());
	}

	public static function get_menu_item($key)
	{
		$menu_items = self::get_menu_items();
		if (isset($menu_items[$key])) {
			return $menu_items[$key];
		}

		return false;
	}

	public static function choice_name($choice, $choices = array())
	{
		if (isset($choices[$choice])) {
			return $choices[$choice];
		}

		foreach ($choices as $c) {
			if (isset($c['name'])) {
				if (isset($c['key']) && $choice == $c['key']) {
					return $c['name'];
				} elseif (isset($c['id']) && $choice == $c['id']) {
					return $c['name'];
				}
			}
		}

		return '';
	}

	public static function order_by_position($a, $b)
	{
		if (!isset($a['position']) || !isset($b['position'])) {
			return -1;
		}
		if ($a['position'] == $b['position']) {
			return 0;
		}
		return ($a['position'] < $b['position']) ? -1 : 1;
	}

	public static function validate_cookie_user()
	{
		if (isset($_COOKIE[LOGGED_IN_COOKIE]) && $user_id = wp_validate_auth_cookie($_COOKIE[LOGGED_IN_COOKIE], 'logged_in')) {
			wp_set_current_user($user_id);
		}
	}


	public static function human_time_diff($time, $now)
	{
		$start_date = new \DateTime($time);
		$diff = $start_date->diff(new \DateTime($now));
		$items = array();

		if ($diff->y > 0) {
			$items[] = sprintf(_n('%s year', '%s years', $diff->m, 'shazzad-wp-logs'), $diff->m);
			if ($diff->m > 0) {
				$items[] = sprintf(_n('%s month', '%s months', $diff->m, 'shazzad-wp-logs'), $diff->m);
			}
		} elseif ($diff->m > 0) {
			$items[] = sprintf(_n('%s month', '%s months', $diff->m, 'shazzad-wp-logs'), $diff->m);
			if ($diff->d > 0) {
				$items[] = sprintf(_n('%s day', '%s days', $diff->d, 'shazzad-wp-logs'), $diff->d);
			}
		} elseif ($diff->d > 0) {
			$items[] = sprintf(_n('%s day', '%s days', $diff->d, 'shazzad-wp-logs'), $diff->d);
			if ($diff->h > 0) {
				$items[] = sprintf(_n('%s hour', '%s hours', $diff->h, 'shazzad-wp-logs'), $diff->h);
			}
		} elseif ($diff->h > 0) {
			$items[] = sprintf(_n('%s hour', '%s hours', $diff->h, 'shazzad-wp-logs'), $diff->h);
			if ($diff->i > 0) {
				$items[] = sprintf(_n('%s min', '%s mins', $diff->i, 'shazzad-wp-logs'), $diff->i);
			}
		} elseif ($diff->i > 0) {
			$items[] = sprintf(_n('%s min', '%s mins', $diff->i, 'shazzad-wp-logs'), $diff->i);
			if ($diff->i < 10 && $diff->s > 0) {
				$items[] = sprintf(_n('%s sec', '%s secs', $diff->s, 'shazzad-wp-logs'), $diff->s);
			}
		} elseif ($diff->s > 0) {
			$items[] = sprintf(_n('%s sec', '%s secs', $diff->s, 'shazzad-wp-logs'), $diff->s);
		}

		return join(', ', $items);
	}

	public static function p($data)
	{
		echo '<pre>';
		print_r($data);
		echo '</pre>';
	}

	public static function d($data)
	{
		self::p($data);
		exit;
	}
}