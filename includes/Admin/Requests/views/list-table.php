<?php
global $list_table_requests;
$list_table_requests->views(); ?>

<form id="posts-filter" action="" method="get">
	<input type="hidden" name="page"
		value="<?php echo isset( $_REQUEST['page'] ) ? urldecode( $_REQUEST['page'] ) : 'shazzad-wp-requests'; ?>" />
	<?php if ( isset( $_REQUEST['menu_item'] ) && $_REQUEST['menu_item'] !== $_REQUEST['page'] ) : ?>
		<input type="hidden" name="menu_item" value="<?php echo urldecode( $_REQUEST['menu_item'] ); ?>" />
	<?php endif; ?>
	<?php $list_table_requests->search_box( __( 'Search Logs', 'w4-requests' ), 'log' ); ?>
	<?php $list_table_requests->display(); ?>
</form>
<?php
