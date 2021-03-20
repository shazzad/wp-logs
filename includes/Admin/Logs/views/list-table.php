<?php
global $w4LogsListTableLogs;
$w4LogsListTableLogs->views(); ?>

<form id="posts-filter" action="" method="get">
	<input type="hidden" name="page" value="w4-loggable" />
	<?php if ( isset( $_REQUEST['menu_item'] ) ) : ?>
		<input type="hidden" name="menu_item" value="<?php echo urldecode( $_REQUEST['menu_item'] ); ?>" />
	<?php endif; ?>
	<?php $w4LogsListTableLogs->search_box(sprintf(__('Search Logs', 'w4-logs')), 'post'); ?>
	<?php $w4LogsListTableLogs->display(); ?>
</form><?php
