<?php
global $w4LogsListTableLogs;
$w4LogsListTableLogs->views(); ?>

<form id="posts-filter" action="" method="get">
	<input type="hidden" name="page" value="<?php echo isset( $_REQUEST['page'] ) ? urldecode( $_REQUEST['page'] ) : 'shazzad-wp-logs'; ?>" />
	<?php if ( isset( $_REQUEST['menu_item'] ) && $_REQUEST['menu_item'] !== $_REQUEST['page'] ) : ?>
		<input type="hidden" name="menu_item" value="<?php echo urldecode( $_REQUEST['menu_item'] ); ?>" />
	<?php endif; ?>
	<?php $w4LogsListTableLogs->search_box( __('Search Logs', 'w4-logs'), 'log' ); ?>
	<?php $w4LogsListTableLogs->display(); ?>
</form><?php
