<?php
	global $wpdb;
	$table_name = $wpdb->prefix.'thor_fcm_users';
	$query = "SELECT * FROM $table_name";
?>