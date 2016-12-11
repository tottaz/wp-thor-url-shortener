<?php
	$send_msg = $wpdb->get_row("SELECT send_msg FROM $table_name WHERE fcm_regid = '$temp'");
	$new_num = $send_msg->send_msg+1;
	$wpdb->query("UPDATE $table_name SET send_msg=$new_num WHERE fcm_regid = '$temp'");
?>