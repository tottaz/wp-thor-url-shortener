<?php

	if(!current_user_can('edit_posts')) {
		wp_die(__('You have not sufficient capabilities to view this page', 'fcm'), __('Change your permission', 'fcm'));
	}

	// get user count
	include THORFCM_PLUGIN_PATH . '/app/models/get_count_users.php';

	$info = sprintf(__('currently %s users are registered','fcm'),$num_rows);

	// get view

	include THORFCM_PLUGIN_PATH . '/app/views/get_post_message_view.php';

	if(isset($_POST['message'])) {
		$message = $_POST["message"];
		$priority = $_POST["priority"];
		$time = $_POST["time"];
		print_r(ThorAdmin::thor_sendFCM($message, "message", 010, $priority, $time));
	}