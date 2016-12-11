<?php

			$sql = "INSERT INTO $table_name (fcm_regid, os, model, created_at) VALUES ('$fcm_regid', '$os', '$model', '$time')";
			$q = $wpdb->query($sql);

?>