<?php
/*
Plugin Name: WP Thor URL Shortner
Plugin URI: https://thunderbeardesign.com/downloads/wp-thor-url-shortener
Description: This is a url shortner that works with https://moln.co
Version: 1.3
Author: ThunderBear Design
Author URI: http://thunderbeardesign.com
Build: 1.3
*/

// Prevent direct access to this file.
if ( ! defined( 'ABSPATH' ) ) {
    header( 'HTTP/1.0 403 Forbidden' );
    echo 'This file should not be accessed directly!';
    exit; // Exit if accessed directly
}

//
define('THORURLSHORTENER_VERSION', '1.3');
define('THORURLSHORTENER_PLUGIN_URL', WP_PLUGIN_URL . '/' . dirname(plugin_basename(__FILE__)));
define('THORURLSHORTENER_PLUGIN_PATH', WP_PLUGIN_DIR . '/' . dirname(plugin_basename(__FILE__)));
define('THORURLSHORTENER_PLUGIN_FILE_PATH', WP_PLUGIN_DIR . '/' . plugin_basename(__FILE__));
define('THORURLSHORTENER_SL_STORE_URL', 'https://thunderbeardesign.com' ); 
define('THORURLSHORTENER_SL_ITEM_NAME', 'WP Thor URL Shortener' );
// the name of the settings page for the license input to be displayed
define('THORURLSHORTENER_PLUGIN_LICENSE_PAGE', 'thor_url_shortener_admin&tab=licenses' );

if( !class_exists( 'EDDURLSHORTENER_SL_Plugin_Updater' ) ) {
	// load our custom updater
	require_once THORURLSHORTENER_PLUGIN_PATH . '/app/edd-include/EDDURLSHORTENER_SL_Plugin_Updater.php';
}

$license_key = trim( get_option( 'edd_thor_urlshort_license_key' ) );
// setup the updater
$edd_updater = new EDDURLSHORTENER_SL_Plugin_Updater( THORURLSHORTENER_SL_STORE_URL, __FILE__, array( 
		'version' 	=> '1.3', 			// current version number
		'license' 	=> $license_key, 	// license key (used get_option above to retrieve from DB)
		'item_name'	=> urlencode( THORURLSHORTENER_SL_ITEM_NAME ), 	// name of this plugin
		'author' 	=> 'ThunderBear Design',  // author of this plugin
		'url'      	=> home_url()
	)
);

//Load The Admin Class
if (!class_exists('ThorURLShortnerAdmin')) {
    require_once THORURLSHORTENER_PLUGIN_PATH . '/app/classes/ThorURLShortnerAdmin.class.php';
}

$obj = new ThorURLShortnerAdmin(); //initiate admin object    

?>