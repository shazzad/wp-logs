<?php
namespace Shazzad\WpLogs\Admin;

/**
 * Admin Environment
 * @package Shazzad\WpLogs
 */


interface PageInterface
{
	public function load_page();
	public function handle_actions();
	public function print_scripts();
	public function render_page();
}
