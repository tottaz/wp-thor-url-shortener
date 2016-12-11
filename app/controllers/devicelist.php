<?php

	if(!current_user_can('edit_posts')) {
		wp_die(__('You have not sufficient capabilities to view this page', 'thor_fcm'), __('No Cheating buddy', 'thor_fcm'));
	}

    require_once THORFCM_PLUGIN_PATH . '/app/classes/ThorList.class.php';

	$wp_list_table = new FCM_List_Table();
	$wp_list_table->prepare_items();
	if(isset($_GET['action']) && $_GET['action'] == 'view') {
	}else {
		?>
		<div class="wrap">
			<h2><?php _e('FCM › All Devices','thor_fcm'); ?></h2>
				<form method="get">
					<input type="hidden" name="page" value="<?php echo $_REQUEST['page']; ?>" />
					<?php $wp_list_table->search_box('search', 'search_id'); ?>
				</form>
			<?php $wp_list_table->display(); ?>
		</div>
	<?php
	}
?>