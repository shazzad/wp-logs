<?php
namespace W4dev\Loggable\Admin;

/**
 * Admin Environment
 * @package WordPress
 * @subpackage SERVED Admin
 * @author Shazzad Hossain Khan
 * @url https://shazzad.me
**/


interface PageInterface
{
	public function load_page();
	public function handle_actions();
	public function print_scripts();
	public function render_page();
}
