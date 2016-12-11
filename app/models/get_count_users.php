<?php
	global $wpdb;
	$table_name = $wpdb->prefix . 'thor_fcm_users';

	$result = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name" );

	if($result != false) {
		$num_rows = $result;
	}else {
		$num_rows = 0;
	}