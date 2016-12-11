<?php

	if(isset($_GET['fcm-upgrade']) ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'thor_fcm_users';
		
		$queryO = "UPDATE $table_name SET `os`= \'not set\' WHERE `os` = \'\' ";
		$wpdb->query($queryO);
		
		$queryM = "UPDATE $table_name SET `model`= \'not set\' WHERE `model` = \'\' ";
		$wpdb->query($queryM);
	}

?>