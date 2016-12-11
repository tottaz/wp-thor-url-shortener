<?php
		$query = "SELECT * FROM $table_name WHERE 
			fcm_regid LIKE '%{$se}%' OR
			os LIKE '%{$se}%' OR
			created_at LIKE '%{$se}%' OR
			id LIKE '%{$se}%' OR
			model LIKE '%{$se}%'";
?>