<?php
/*
Plugin Name: WP Thor URL Shortener
Plugin URI: Thunderbear Design
Description: Creats a frontend for your website to shorten URLs via your URL Shortener!
Version: 1.0
Author: Torbjorn Zetterlund
*/
include("Core.class.php");

if(is_admin()) {
	add_action('admin_menu', array('PUS_Short','admin_menu'));
	add_action('admin_init', array('PUS_Short','admin_setting'));
 	add_action('admin_enqueue_scripts', array('PUS_Short','admin_css'));
 	add_action('admin_enqueue_scripts', array('PUS_Short','admin_js'));
	register_activation_hook(__FILE__, array('PUS_Short','install'));	 	
	register_deactivation_hook(__FILE__, array('PUS_Short','uninstall')); 		
}
add_action('widgets_init', array('PUS_Short','register_widget'));			 	
add_action('wp_enqueue_scripts', array('PUS_Short','js'));
add_action('wp_enqueue_scripts', array('PUS_Short','css'));
add_action( 'add_meta_boxes', array('PUS_Short','add_to_post'), 10, 2 );
PUS_Short::start(get_option('shortener_settings'));