<?php
namespace W4dev\Loggable\Admin;

/**
 * Admin Environment
 * @package W4dev\Loggable
 */


interface PageInterface
{
	public function load_page();
	public function handle_actions();
	public function print_scripts();
	public function render_page();
}
