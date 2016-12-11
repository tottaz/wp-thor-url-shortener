<?php
	global $wpdb;

	$table_name = $wpdb->prefix.'thor_fcm_users';


	/*---------------------- Total Num DATA ----------------------------------------*/

	$all = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name" );


	if($all != false) {
		$num_rows = $all;
	}else {
		$num_rows = 0;
	}
	
	/*---------------------- OS DATA ----------------------------------------*/

	
	$sqlO = "SELECT os, COUNT(*) FROM $table_name GROUP BY os";
	$resO = $wpdb->get_results($sqlO, ARRAY_A);
	
	/*---------------------- MODEL DATA ----------------------------------------*/
	$sqlM = "SELECT model, COUNT(*) FROM $table_name GROUP BY model";
	$resM = $wpdb->get_results($sqlM, ARRAY_A);