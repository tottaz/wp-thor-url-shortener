<?php

// Delete Action
	$device = $_GET['device'];

	// Delete Device
	include THORFCM_PLUGIN_PATH . '/app/models/delete_device.php';

	//Show list of Devices
	require_once THORFCM_PLUGIN_PATH . '/app/controllers/devicelist.php';