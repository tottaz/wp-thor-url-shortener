<?php

	$id = $_GET['device'];
	$total_msg = get_option('fcm_total_msg',0);

	// get sql
	include THORFCM_PLUGIN_PATH . '/app/models/get_users_detail.php';

	$num = $this->num($device->send_msg);
	$this->fcm_display_view(
		$device->id,
		$device->fcm_regid,
		$device->os,
		$device->model,
		$device->created_at,
		$num,
		$total_msg);
?>