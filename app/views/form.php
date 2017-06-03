<div id="Thor_main" <?php if(isset($theme)) echo "class='".$theme."-c'"; ?>>					
	<div id="Thor_message"></div>				
	<!-- /#Thor_message -->
	<form action="<?php echo $options['thor_url_shortener_url']; ?>" id="Thor_form">
		<div id="Thor_main_input">
			<label for="Thor_main_input"><?php _e("Long URL",'shortener-plugin') ?></label>
			<input type="text" placeholder="http://" id="Thor_url">	
			<span id="Thor_loading"></span>			
		</div>
		<!-- /#Thor_main_input -->
		<div id="Thor_custom_container">
			<div id="PU_custom_input_container">
				<label for="Thor_custom_input"><?php _e("Custom Alias",'shortener-plugin') ?></label>
				<span><label for="Thor_custom_input"><?php if($options['thor_url_shortener_url']) echo $options['thor_url_shortener_url']?></label></span>
				<input type="text" id="Thor_custom_input" placeholder="e.g. apple">				
			</div>
			<input type="hidden" id="Thor_share_text" value="<?php if($options['thor_url_shortener_share']) echo $options['thor_url_shortener_share']?>">
			<input type="hidden" id="Thor_token" value="<?php if($options['thor_url_shortener_apikey']) echo $options['thor_url_shortener_apikey']?>">
			<!-- /#PU_custom_input_container -->
			<button type="submit"><?php _e("Shorten",'shortener-plugin') ?></button>
		</div>
		<!-- /#Thor_custom_container -->
	</form>
	<!-- /#Thor_form -->
</div>	