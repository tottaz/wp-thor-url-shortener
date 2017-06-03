<div class="wrap">
<?php if($updated): ?>
	<div id="message" class="updated below-h2"><p>Premium URL Shortener Settings Updated.</p></div>	
<?php endif ?>	
	<div id="poststuff" class="metabox-holder has-right-sidebar">
		<div style="width:48%;float:left;overflow:hidden;" class="postbox">
			<h3><?php _e('URL Shortener Settings', 'shortener-plugin'); ?></h3>
			<div class="inside">
				<form method="post" action="options.php" class="shortener-form">					
					<?php settings_fields('shortener_setting_group'); ?>
					<p>
						<label class="description" for="shortener_settings[shortener_url]"><?php _e('Enter the URL of the shortener ', 'shortener-plugin'); ?></label>
						<span class="subdescription">
							The URL to your site (e.g. http://mysite.com/)
						</span>									
						<input type="text" name="shortener_settings[shortener_url]" id="shortener_settings[shortener_url]" value="<?php echo self::$config['shortener_url']; ?>" />
					</p>
					<p>
						<label class="description" for="shortener_settings[shortener_api]"><?php _e('Enter Your API key ', 'shortener-plugin'); ?></label>
						<span class="subdescription">
							An API key is required to shorten URLs using your URL Shortener.
						</span>						
						<input type="text" name="shortener_settings[shortener_api]" id="shortener_settings[shortener_api]" value="<?php echo self::$config['shortener_api']; ?>" />
					</p>		
					<p>
						<label class="description" for="shortener_settings[shortener_theme]">Default Widget and Ajax Form Theme</label>
						<span class="subdescription">
							Choose a theme for the URL shortener widget (change and see preview on the right side).
						</span>
						<select name="shortener_settings[shortener_theme]" id="shortener_settings[shortener_theme]" class="PUS_theme-trigger">							
							<option <?php if(!self::$config["shortener_theme"]=="default") echo 'selected="selected"' ?> value="default">Default</option>
							<option <?php if(self::$config["shortener_theme"]=="tb") echo 'selected="selected"' ?> value="tb">Transparent &amp; Black</option>
							<option <?php if(self::$config["shortener_theme"]=="gb") echo 'selected="selected"' ?> value="gb">Green &amp; Blue</option>
							<option <?php if(self::$config["shortener_theme"]=="db") echo 'selected="selected"' ?> value="db">Dark Blue</option>
							<option <?php if(self::$config["shortener_theme"]=="dr") echo 'selected="selected"' ?> value="dr">Dark Red</option>
							<option <?php if(self::$config["shortener_theme"]=="lg") echo 'selected="selected"' ?> value="lg">Light Green</option>
							<option <?php if(self::$config["shortener_theme"]=="custom") echo 'selected="selected"' ?> value="cc">Custom</option>
						</select>
					</p>										
					<p>
						<label class="description" for="shortener_settings[shortener_comment]">Comment URL Shortening</label>
						<span class="subdescription">
							Enabling this will shorten all external URLs in the comment section.
						</span>
						<select name="shortener_settings[shortener_comment]" id="shortener_settings[shortener_comment]">
							<option <?php if(self::$config["shortener_comment"]) echo 'selected="selected"' ?> value="1">Enabled</option>
							<option <?php if(!self::$config["shortener_comment"]) echo 'selected="selected"' ?> value="0">Disabled</option>
						</select>
					</p>
					<p>
						<label class="description" for="shortener_settings[shortener_shortcode]">Shortcode</label>
						<span class="subdescription">
							Enabling this will allow you to use shortcodes to shorten URLs within your post (Choose shortcode below).
						</span>
						<select name="shortener_settings[shortener_shortcode]" id="shortener_settings[shortener_shortcode]">							
							<option <?php if(!self::$config["shortener_shortcode"]) echo 'selected="selected"' ?> value="0">Disabled</option>
							<option <?php if(self::$config["shortener_shortcode"]=="shorten") echo 'selected="selected"' ?> value="shorten">[shorten]</option>
							<option <?php if(self::$config["shortener_shortcode"]=="shorten_url") echo 'selected="selected"' ?> value="shorten_url">[short_url]</option>
							<option <?php if(self::$config["shortener_shortcode"]=="srt") echo 'selected="selected"' ?> value="srt">[srt]</option>
						</select>
					</p>	
					<p>
						<label class="description" for="shortener_settings[shortener_shortcode_type]">Choose Shortening Type for the Short Code</label>
						<span class="subdescription">
							Choose whether to use Javascript or PHP to shorten URL when using shortcode. Javascript is faster but PHP shows the short URL in the source code. PHP is recommended if you want to shorten less than 3 links in your posts/page and Javascript is recommended if you want to shorten more than 3 links.
						</span>
						<select name="shortener_settings[shortener_shortcode_type]" id="shortener_settings[shortener_shortcode_type]">
							<option <?php if(self::$config["shortener_shortcode_type"]=="php") echo 'selected="selected"' ?> value="php">PHP (Server-Side)</option>
							<option <?php if(self::$config["shortener_shortcode_type"]=="js") echo 'selected="selected"' ?> value="js">Javascript (Client-Side)</option>
						</select>
					</p>						
					<p>
						<label class="description" for="shortener_settings[shortener_share]">Twitter Custom Text</label>
						<span class="subdescription">
							The custom text to pre-fill tweets with (e.g. Shortened via <?php echo self::$config["shortener_url"] ?>)
						</span>
						<textarea name="shortener_settings[shortener_share]" id="shortener_settings[shortener_share]"><?php if(self::$config["shortener_share"]) echo self::$config["shortener_share"] ?></textarea>
					</p>																			
					<p class="submit">
						<input class="button-primary" type="submit" name="Save" value="<?php _e('Save Changes', 'shortener-plugin'); ?>" />
					</p>
				</form>
			</div>
		</div>			
		<div style="width:49.5%;float:left;overflow:hidden;margin-left:2%;" class="postbox right">
			<h3>Widget Theme Preview (<small id="PUS_widget_title">default</small>)</h3>
			<div id="PUS_demo" class="inside">
				<p class="PUS_custom_message" style="display:none">To add a custom theme, open themes/themes.css and find <code>#PUS_main.cc-c</code> then add your custom styles.</p>				
				<div id="PUS_main" <?php if(isset(self::$config["shortener_theme"])) echo "class='".self::$config["shortener_theme"]."-c'"; ?>>					
					<div id="PUS_message" class="PUS_error" style="display:block !important;">Please enter a valid URL (including http://)</div>					
					<!-- /#PUS_message -->
					<form action="#" id="PUS_form">
						<div id="PUS_main_input">
							<label for="PUS_main_input">Long URL</label>
							<input type="text" placeholder="http://">	
							<span id="PUS_loading">Loading...</span>			
						</div>
						<!-- /#PUS_main_input -->
						<div id="PUS_custom_container">
							<div id="PU_custom_input_container">
								<label for="PUS_custom_input"><?php _e("Custom Alias",'shortener-plugin') ?></label>
								<span><label for="PUS_custom_input"><?php if(self::$config["shortener_url"]) echo self::$config["shortener_url"]?></label></span>
								<input type="text" id="PUS_custom_input" placeholder="e.g. apple">				
							</div>
							<button type="button" href="#">Shorten</button>
						</div>
						<!-- /#PUS_custom_container -->
					</form>
					<!-- /#PUS_form -->
				</div>	
			</div>
		</div>
		<div style="width:49.5%;float:left;overflow:hidden;margin-left:2%;" class="postbox right">
			<h3>Documentation</h3>
			<div class="inside">
				<h3 id="conf">1.0 Configuration</h3>
				<p>
					You must configure this plugin before you can use it. First make sure to add the proper information such as the URL where the shortener is located. For example if your shortener is located at http://gempixel.com/short/ then you should input <code>http://gempixel.com/short/</code>. Your API key can be found in the User Dashboard. Also please make sure that API feature is enabled in the script.
				</p>
				<h3 id="sc">2.0 Shortcodes</h3>
				<p>
					You can use some shortcodes to either shorten a URL or to show the Ajax form in your page or post. To shorten a URL within your post or page, you can use the shortcode as defined in the options on the left side. 
				</p>
					<h4>2.1 Shortcode Example</h4>
				<p>
					The shortcode has only 1 attribute and that is to show the html link (link=true).
					<code>[shorten]http://google.com[/shorten]</code> will ouput <code><?php if(self::$config["shortener_url"]) echo self::$config["shortener_url"]?>SomeAlias</code>.
				</p>
				<p>
					<code>[shorten link=true]http://google.com[/shorten]</code> will output <code>&lt;a href='<?php if(self::$config["shortener_url"]) echo self::$config["shortener_url"]?>SomeAlias' rel='nofollow' target='_blank'&gt;<?php if(self::$config["shortener_url"]) echo self::$config["shortener_url"]?>SomeAlias&lt;/a&gt;</code>.					
				</p>
				<h4>2.2 Ajax Form in post or page</h4>
				<p>To show the Ajax form in your post or page, you can use the shortcode <code>[show_shortener_form]</code>. This shortcode doesn't have any attributes. The style can be changed via the options on left side.</p>

				<h3 id="wd">3.0 Wdiget</h3>
				<p>You can use the widget by activating it in widget settings. The theme of the widget can be chosen from widget settings.</p>

				<h3 id="support">4.0 Questions and Support</h3>
				<p>If you have any questions, you can <a href="http://support.gempixel.com" target="_blank">open a ticket</a> or <a href="http://gempixel.com/profile" target="_blank">send an email</a>.</p>
			</div>
		</div>					
	</div>
</div>