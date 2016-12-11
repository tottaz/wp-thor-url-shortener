<?php
if(isset($_GET['subtab'])) $subtab=$_GET['subtab'];
else $subtab ='';
?>
<div class="thor-subtab-menu">
	<a class="thor-subtab-menu-item" href="<?php echo $url.'&tab=general_settings&subtab=fcm';?>"> FCM</a>
	<a class="thor-subtab-menu-item" href="<?php echo $url.'&tab=general_settings&subtab=export';?>">Export</a>
</div>
<?php

if(!current_user_can('manage_options')) {
	wp_die(__('You have not sufficient capabilities to view this page', 'fcm'), __('Get the right permission', 'fcm'));
}
	
$plugins_url = plugins_url();

?>
<div class="thor-stuffbox" style="margin-top: 50px;">
	<h3 class="thor-h3"><?php _e('FCM › Settings','fcm'); ?></h3>
	  <?php
		if (isset($_GET['settings-updated'])) {
			echo '<div class="updated" ><p>'.__('Settings saved', 'fcm').'</p></div>';
		}			
?>
<div class="thor-settings-wrap">
	<div class="thor-stuffbox" style="margin-top: 30px;">
	<?php switch($subtab){ 
	    case 'export': ?>
		<?php if( isset($_POST['ok']) ) {
			$li = ThorAdmin::thor_fcm_export();
			?>
			<div id="message" class="updated">
				<p><strong><?php _e('FCM export finished', 'fcm'); ?></strong></p>
				<p><?php printf( __('%1$s click here %2$s to download', 'fcm'),'<a href='.$li.' download>' ,'</a>'); ?></p>
			</div>
		<?php } ?>
		
		<div class="inside">
			<form method="post" action="#">
				<p>
					<input type="hidden" name="ok" value="ok">
					<?php _e('Export the whole Database into an excel readable file.', 'fcm'); ?>
				</p>
				
				<p>
					<?php submit_button(__('Export', 'fcm')); ?>
				</p>
			</form>
		</div>
		<?php break; ?>	
						
		<?php default: ?>	
		<div class="inside">
			<form method="post" action="options.php">
				<div id="settings">
					<?php settings_fields('thor-fcm-settings', 'thor_fcm_settings_section'); ?>
					<?php do_settings_sections('thor-fcm-settings'); ?>
					<?php submit_button(); ?>
				</div>
			</form>
			<p>Check the <a target="_blank" href="<?php echo $plugins_url; ?>/wp-thor-fcm/documentation/index.html">Documentation</a> for help!</p>
		</div>			
		<?php
		} ?>
	<br class="clear">
  </div>
</div>