<?php
if(!current_user_can('manage_options')) {
	wp_die(__('You have not sufficient capabilities to view this page', 'thor_url_shortener'), __('Get the right permission', 'thor_url_shortener'));
}
?>
<div class="thor-stuffbox" style="margin-top: 50px;">
	<h3 class="thor-h3"><?php _e('URL Shortener › Settings','thor_url_shortener'); ?></h3>
	  <?php
		if (isset($_GET['settings-updated'])) {
			echo '<div class="updated" ><p>'.__('Settings saved', 'thor_url_shortener').'</p></div>';
		}			
?>
	<div class="thor-settings-wrap">
		<div class="thor-stuffbox" style="margin-top: 30px;">
			<div class="inside">
				<form method="post" action="options.php">
					<div id="settings">
						<?php settings_fields('thor-url-shortener-settings', 'thor_url_shortener_settings_section'); ?>
						<?php do_settings_sections('thor-url-shortener-settings'); ?>
						<?php submit_button(); ?>
					</div>
				</form>
			</div>			
		<br class="clear">
	  </div>
	</div>
</div>