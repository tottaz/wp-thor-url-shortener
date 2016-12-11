<?php
	    global $wpdb;
	    $table_name = $wpdb->prefix.'thor_fcm_users';
	    $devices = array();
	    $sql = "SELECT fcm_regid FROM $table_name";
	    $res = $wpdb->get_results($sql);
?>