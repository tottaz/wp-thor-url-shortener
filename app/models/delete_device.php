<?php
	global $wpdb;

	$table_name = $wpdb->prefix.'thor_fcm_users';
	$result = $wpdb->query($wpdb->prepare("DELETE FROM $table_name WHERE id = %s",$device));
?>