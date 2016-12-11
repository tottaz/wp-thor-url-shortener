<?php

	if(!current_user_can('edit_posts')) {
		wp_die(__('You have not sufficient capabilities to view this page', 'fcm'), __('Change your permission', 'fcm'));
	}

	// get the variables
	include THORFCM_PLUGIN_PATH . '/app/helpers/variables.php';

	$color1 = array();
	$color2 = array();
	$run = 1;
	for($i=0; $i<1000; $i++) {
		array_push($color1, $dark[$run]);
		array_push($color2, $light[$run]);
		if($run == 15) {
			$run = 1;	
		}else {
			$run++;	
		}
	}
	// Get the model and data
	include THORFCM_PLUGIN_PATH . '/app/models/dashboard_data.php';
	// get the view
	include THORFCM_PLUGIN_PATH . '/app/views/dashboard.php';