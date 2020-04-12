<?php
global $w4LogsListTableLogs;
$w4LogsListTableLogs->views(); ?>

<form id="posts-filter" action="" method="get">
  <input type="hidden" name="page" value="<?php echo 'w4-loggable-logs'; ?>" />
  <?php $w4LogsListTableLogs->search_box(sprintf(__('Search Logs', 'w4-logs')), 'post'); ?>
  <?php $w4LogsListTableLogs->display(); ?>
</form><?php
