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
class Utils {

	public static function get_menu_items() {
		return apply_filters( 'swpl_menu_items', [] );
	}

	public static function get_menu_item( $key ) {
		$menu_items = self::get_menu_items();
		if ( isset( $menu_items[$key] ) ) {
			return $menu_items[$key];
		}

		return false;
	}

	public static function choice_name( $choice, $choices = [] ) {
		if ( isset( $choices[$choice] ) ) {
			return $choices[$choice];
		}

		foreach ( $choices as $c ) {
			if ( isset( $c['name'] ) ) {
				if ( isset( $c['key'] ) && $choice == $c['key'] ) {
					return $c['name'];
				} elseif ( isset( $c['id'] ) && $choice == $c['id'] ) {
					return $c['name'];
				}
			}
		}

		return '';
	}
}