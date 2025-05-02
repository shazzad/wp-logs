<?php
global $list_table_logs;
$list_table_logs->views(); ?>

<form id="posts-filter" action="" method="get">
	<input type="hidden" name="page"
		value="<?php echo isset( $_REQUEST['page'] ) ? urldecode( $_REQUEST['page'] ) : 'shazzad-wp-logs'; ?>" />
	<?php if ( isset( $_REQUEST['menu_item'] ) && $_REQUEST['menu_item'] !== $_REQUEST['page'] ) : ?>
		<input type="hidden" name="menu_item" value="<?php echo urldecode( $_REQUEST['menu_item'] ); ?>" />
	<?php endif; ?>
	<?php $list_table_logs->search_box( __( 'Search Logs', 'w4-logs' ), 'log' ); ?>
	<?php $list_table_logs->display(); ?>
</form>
<?php
