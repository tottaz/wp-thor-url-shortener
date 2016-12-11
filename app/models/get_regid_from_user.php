<?php
		$table_name = $wpdb->prefix .'thor_fcm_users';
		$sql = "SELECT fcm_regid FROM $table_name WHERE fcm_regid='$fcm_regid'";
		$result = $wpdb->get_results($sql);
?>